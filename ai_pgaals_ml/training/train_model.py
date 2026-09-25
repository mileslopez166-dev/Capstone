from __future__ import annotations

from pathlib import Path

import joblib
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline


ROOT = Path(__file__).resolve().parents[1]
DATASET = ROOT / "processed" / "clean_dataset.csv"
MODEL_PATH = ROOT / "models" / "reading_level_model.pkl"
REPORT_PATH = ROOT / "processed" / "model_evaluation_report.md"
IMPORTANCE_PATH = ROOT / "processed" / "feature_importance.csv"

FEATURE_COLUMNS = [
    "reading_score",
    "reading_accuracy",
    "reading_speed",
    "comprehension_score",
    "listening_score",
    "numeracy_score",
    "assessment_attempts",
    "previous_score",
    "completion_time",
    "intervention_count",
]
TARGET = "reading_level_label"


def available_feature_columns(frame: pd.DataFrame) -> list[str]:
    return [column for column in FEATURE_COLUMNS if frame[column].notna().any()]


def load_dataset() -> pd.DataFrame:
    if not DATASET.exists():
        raise FileNotFoundError(f"Missing dataset: {DATASET}. Run preprocessing/merge_dataset.py first.")
    frame = pd.read_csv(DATASET)
    missing = [column for column in FEATURE_COLUMNS + [TARGET] if column not in frame.columns]
    if missing:
        raise ValueError(f"Dataset is missing required columns: {missing}")
    frame = frame.dropna(subset=[TARGET])
    if frame[TARGET].nunique() < 2:
        raise ValueError("Training requires at least two reading level classes.")
    if len(frame) < 10:
        raise ValueError("Training requires at least 10 rows. Add more processed dataset records.")
    if not available_feature_columns(frame):
        raise ValueError("Training requires at least one feature column with data.")
    return frame


def train() -> dict:
    frame = load_dataset()
    features = available_feature_columns(frame)
    ignored_features = [column for column in FEATURE_COLUMNS if column not in features]
    x = frame[features]
    y = frame[TARGET]
    stratify = y if y.value_counts().min() >= 2 else None
    x_train, x_test, y_train, y_test = train_test_split(
        x,
        y,
        test_size=0.2,
        random_state=42,
        stratify=stratify,
    )

    model = Pipeline([
        ("imputer", SimpleImputer(strategy="median")),
        ("classifier", RandomForestClassifier(n_estimators=100, random_state=42)),
    ])
    model.fit(x_train, y_train)
    predictions = model.predict(x_test)
    probabilities = model.predict_proba(x_test)

    report = classification_report(y_test, predictions, zero_division=0)
    matrix = confusion_matrix(y_test, predictions, labels=list(model.classes_))
    accuracy = accuracy_score(y_test, predictions)

    MODEL_PATH.parent.mkdir(parents=True, exist_ok=True)
    package = {
        "model": model,
        "features": features,
        "ignored_features": ignored_features,
        "classes": list(model.classes_),
        "model_name": "reading_level_random_forest_v1",
    }
    joblib.dump(package, MODEL_PATH)

    importance = pd.DataFrame({
        "feature": features,
        "importance": model.named_steps["classifier"].feature_importances_,
    }).sort_values("importance", ascending=False)
    importance.to_csv(IMPORTANCE_PATH, index=False)

    REPORT_PATH.write_text(
        "# Model Evaluation Report\n\n"
        f"Model: reading_level_random_forest_v1\n\n"
        f"Rows: {len(frame)}\n\n"
        f"Features used: {', '.join(features)}\n\n"
        f"Ignored empty features: {', '.join(ignored_features) if ignored_features else 'None'}\n\n"
        f"Accuracy: {accuracy:.4f}\n\n"
        "## Classification Report\n\n"
        f"```text\n{report}\n```\n\n"
        "## Confusion Matrix\n\n"
        f"Labels: {list(model.classes_)}\n\n"
        f"```text\n{matrix}\n```\n\n"
        "## Note\n\n"
        "This model is a support tool only. It does not replace Phil-IRI scoring or teacher judgment.\n",
        encoding="utf-8",
    )

    return {
        "accuracy": accuracy,
        "rows": len(frame),
        "classes": list(model.classes_),
        "features": features,
        "sample_probabilities_shape": probabilities.shape,
    }


if __name__ == "__main__":
    result = train()
    print(result)
    print(f"Wrote {MODEL_PATH}")
    print(f"Wrote {REPORT_PATH}")
    print(f"Wrote {IMPORTANCE_PATH}")
