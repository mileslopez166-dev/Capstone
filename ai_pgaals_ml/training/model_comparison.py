from __future__ import annotations

import hashlib
import json
import time
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import joblib
import numpy as np
import pandas as pd
from sklearn.base import clone
from sklearn.ensemble import RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix, f1_score, precision_score, recall_score
from sklearn.model_selection import GridSearchCV, GroupShuffleSplit, StratifiedGroupKFold, cross_validate
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier

SEED = 42
FEATURES = ["reading_score", "reading_accuracy", "reading_speed", "comprehension_score", "listening_score",
            "numeracy_score", "assessment_attempts", "previous_score", "completion_time", "intervention_count"]
LABELS = {
    "literacy": ["Frustration", "Instructional", "Independent"],
    # These labels require an approved numeracy definition; this module never derives them from Phil-IRI.
    "numeracy": ["Beginning", "Developing", "Proficient", "Advanced"],
}


class InsufficientDataError(ValueError):
    pass


def pipeline(classifier: Any, scale: bool = False) -> Pipeline:
    steps: list[tuple[str, Any]] = [("imputer", SimpleImputer(strategy="median"))]
    if scale:
        steps.append(("scaler", StandardScaler()))
    steps.append(("classifier", classifier))
    return Pipeline(steps)


def candidates() -> dict[str, tuple[Pipeline, dict[str, list[Any]]]]:
    return {
        "decision_tree": (pipeline(DecisionTreeClassifier(random_state=SEED, class_weight="balanced")), {
            "classifier__max_depth": [3, 6, None], "classifier__min_samples_leaf": [1, 3, 8]}),
        "random_forest": (pipeline(RandomForestClassifier(n_estimators=200, random_state=SEED,
            class_weight="balanced_subsample", n_jobs=-1)), {
            "classifier__max_depth": [6, 12, None], "classifier__min_samples_leaf": [1, 3]}),
        "svm": (pipeline(SVC(probability=True, random_state=SEED, class_weight="balanced"), True), {
            "classifier__C": [0.5, 1.0, 2.0], "classifier__kernel": ["rbf"], "classifier__gamma": ["scale"]}),
    }


def prepare(records: list[dict[str, Any]], task: str, minimum: int) -> tuple[pd.DataFrame, list[str]]:
    if task not in LABELS:
        raise ValueError(f"Unsupported task: {task}")
    frame = pd.DataFrame(records)
    if not {"student_code", "proficiency_label"}.issubset(frame.columns):
        raise InsufficientDataError("Anonymous student codes and approved proficiency labels are required.")
    for feature in FEATURES:
        if feature not in frame:
            frame[feature] = np.nan
        frame[feature] = pd.to_numeric(frame[feature], errors="coerce")
    frame = frame[frame.proficiency_label.isin(LABELS[task])].dropna(subset=["student_code", "proficiency_label"])
    frame = frame.drop_duplicates().copy()
    frame["student_code"] = frame.student_code.astype(str)
    usable = [feature for feature in FEATURES if frame[feature].notna().any()]
    distribution = frame.proficiency_label.value_counts()
    if len(frame) < minimum:
        raise InsufficientDataError(f"At least {minimum} labeled records are required; received {len(frame)}.")
    if distribution.size < 2 or distribution.min() < 4:
        raise InsufficientDataError("At least two classes with four records each are required.")
    if frame.student_code.nunique() < 5:
        raise InsufficientDataError("At least five distinct learners are required for learner-grouped evaluation.")
    if not usable:
        raise InsufficientDataError("No usable assessment features were supplied.")
    return frame, usable


def metric_summary(actual: pd.Series, predicted: np.ndarray, labels: list[str]) -> dict[str, Any]:
    report = classification_report(actual, predicted, labels=labels, output_dict=True, zero_division=0)
    return {
        "accuracy": float(accuracy_score(actual, predicted)),
        "precision_macro": float(precision_score(actual, predicted, average="macro", zero_division=0)),
        "recall_macro": float(recall_score(actual, predicted, average="macro", zero_division=0)),
        "f1_macro": float(f1_score(actual, predicted, average="macro", zero_division=0)),
        "confusion_matrix": confusion_matrix(actual, predicted, labels=labels).tolist(), "labels": labels,
        "per_class": {label: {"precision": float(report[label]["precision"]),
            "recall": float(report[label]["recall"]), "f1": float(report[label]["f1-score"]),
            "support": int(report[label]["support"])} for label in labels},
    }


def learner_group_split(frame: pd.DataFrame) -> tuple[pd.DataFrame, pd.DataFrame]:
    split = GroupShuffleSplit(n_splits=1, test_size=.2, random_state=SEED)
    development_index, test_index = next(split.split(frame, frame.proficiency_label, frame.student_code))
    development, held_out = frame.iloc[development_index].copy(), frame.iloc[test_index].copy()
    if set(development.student_code) & set(held_out.student_code):
        raise RuntimeError("Learner leakage detected in the held-out split.")
    return development, held_out


def compare_models(records: list[dict[str, Any]], task: str, model_root: Path, minimum: int = 30) -> dict[str, Any]:
    started = datetime.now(timezone.utc)
    frame, features = prepare(records, task, minimum)
    labels = [label for label in LABELS[task] if label in set(frame.proficiency_label)]
    development, held_out = learner_group_split(frame)
    class_groups = development.groupby("proficiency_label").student_code.nunique()
    folds = min(5, int(class_groups.min()), development.student_code.nunique())
    if folds < 2:
        raise InsufficientDataError("Not enough learner groups per class for grouped cross-validation.")
    cv = StratifiedGroupKFold(n_splits=folds, shuffle=True, random_state=SEED)
    x, y, groups = development[features], development.proficiency_label, development.student_code
    version = started.strftime("%Y%m%dT%H%M%SZ")
    output = model_root / task / version
    output.mkdir(parents=True, exist_ok=True)
    models: dict[str, Any] = {}
    best_estimators: dict[str, Pipeline] = {}
    scoring = {name: name for name in ["accuracy", "precision_macro", "recall_macro", "f1_macro"]}
    for algorithm, (estimator, parameters) in candidates().items():
        search = GridSearchCV(estimator, parameters, scoring="f1_macro", refit=True, cv=cv, n_jobs=-1, error_score="raise")
        search.fit(x, y, groups=groups)
        validation = cross_validate(clone(search.best_estimator_), x, y, groups=groups, cv=cv,
                                    scoring=scoring, n_jobs=-1, error_score="raise")
        metrics = {name: float(np.mean(validation[f"test_{name}"])) for name in scoring}
        metrics.update({"folds": folds, "standard_deviation_f1": float(np.std(validation["test_f1_macro"])),
                        "best_parameters": search.best_params_})
        fitted = clone(search.best_estimator_).fit(frame[features], frame.proficiency_label)
        artifact = output / f"{algorithm}.pkl"
        model_name = f"{task}_{algorithm}_{version}"
        joblib.dump({"model": fitted, "features": features, "classes": list(fitted.classes_),
                     "model_name": model_name, "algorithm": algorithm, "task": task, "version": version,
                     "evaluation": {"method": "learner_grouped_cross_validation", "f1_macro": metrics["f1_macro"],
                                    "folds": folds, "rows": len(frame), "learners": frame.student_code.nunique()}}, artifact)
        best_estimators[algorithm] = search.best_estimator_
        models[algorithm] = {"status": "validated", "model_name": model_name, "version": version,
                             "artifact_path": str(artifact), "validation_metrics": metrics}
    recommended = max(models, key=lambda name: models[name]["validation_metrics"]["f1_macro"])
    winner = clone(best_estimators[recommended]).fit(x, y)
    clock = time.perf_counter()
    predictions = winner.predict(held_out[features])
    latency = (time.perf_counter() - clock) * 1000 / max(1, len(held_out))
    test_metrics = metric_summary(held_out.proficiency_label, predictions, labels)
    test_metrics.update({"method": "learner_grouped_final_holdout", "rows": len(held_out),
                         "learners": held_out.student_code.nunique(), "algorithm": recommended,
                         "prediction_latency_ms_per_row": round(latency, 4)})
    winner_artifact = output / f"{recommended}.pkl"
    winner_package = joblib.load(winner_artifact)
    winner_package["evaluation"] = {
        "method": "held_out_test",
        "accuracy": test_metrics["accuracy"],
        "test_rows": len(held_out),
        "f1_macro": test_metrics["f1_macro"],
    }
    joblib.dump(winner_package, winner_artifact)
    fingerprint = hashlib.sha256(pd.util.hash_pandas_object(
        frame[["student_code", "proficiency_label"] + features], index=False).values.tobytes()).hexdigest()
    result = {"status": "evaluated", "task": task, "version": version, "training_date": started.isoformat(),
              "random_seed": SEED, "dataset": {"rows": len(frame), "learners": frame.student_code.nunique(),
              "class_distribution": frame.proficiency_label.value_counts().to_dict(), "features": features,
              "fingerprint": fingerprint, "development_rows": len(development), "held_out_rows": len(held_out)},
              "evaluation_protocol": {"model_selection": f"{folds}-fold stratified learner-grouped cross-validation",
              "final_test": "20% learner-grouped holdout used once for the recommended candidate",
              "selection_metric": "macro F1"}, "models": models, "recommended_algorithm": recommended,
              "final_test_metrics": test_metrics}
    manifest = model_root / "comparisons" / task
    manifest.mkdir(parents=True, exist_ok=True)
    (manifest / "latest.json").write_text(json.dumps(result, indent=2), encoding="utf-8")
    return result
