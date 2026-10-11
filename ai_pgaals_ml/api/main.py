from __future__ import annotations

import os
import json
import secrets
from pathlib import Path
from typing import Any, Literal, Optional

import joblib
import pandas as pd
from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel, Field


ROOT = Path(__file__).resolve().parents[1]
MODEL_PATH = Path(os.getenv("ML_MODEL_PATH", ROOT / "models" / "reading_level_model.pkl"))
MODEL_ROOT = Path(os.getenv("ML_MODEL_ROOT", MODEL_PATH.parent))
ACTIVE_REGISTRY = Path(os.getenv("ML_ACTIVE_REGISTRY", MODEL_ROOT / "active_models.json"))
ADMIN_TOKEN = os.getenv("ML_ADMIN_TOKEN", "")

app = FastAPI(title="AI-PGAALS ML API", version="1.0.0")
_packages: dict[str, tuple[float, dict[str, Any]]] = {}


class PredictionInput(BaseModel):
    task: Literal["literacy", "numeracy"] = "literacy"
    reading_score: Optional[float] = Field(default=None, ge=0, le=100)
    reading_accuracy: Optional[float] = Field(default=None, ge=0, le=100)
    reading_speed: Optional[float] = Field(default=None, ge=0)
    comprehension_score: Optional[float] = Field(default=None, ge=0, le=100)
    listening_score: Optional[float] = Field(default=None, ge=0, le=100)
    numeracy_score: Optional[float] = Field(default=None, ge=0, le=100)
    assessment_attempts: Optional[float] = Field(default=1, ge=0)
    previous_score: Optional[float] = Field(default=None, ge=0, le=100)
    completion_time: Optional[float] = Field(default=None, ge=0)
    intervention_count: Optional[float] = Field(default=0, ge=0)


class ModelEvaluation(BaseModel):
    method: Literal["held_out_test", "learner_grouped_cross_validation"]
    accuracy: Optional[float] = Field(default=None, ge=0, le=1)
    test_rows: Optional[int] = Field(default=None, gt=0)
    f1_macro: Optional[float] = Field(default=None, ge=0, le=1)
    folds: Optional[int] = Field(default=None, ge=2)
    rows: Optional[int] = Field(default=None, gt=0)
    learners: Optional[int] = Field(default=None, gt=0)


class PredictionOutput(BaseModel):
    prediction: str
    confidence: float
    model_name: str
    evaluation: Optional[ModelEvaluation] = None


class EvaluationInput(BaseModel):
    task: Literal["literacy", "numeracy"]
    records: list[dict[str, Any]]
    minimum_rows: int = Field(default=30, ge=10, le=100000)


class ActivationInput(BaseModel):
    task: Literal["literacy", "numeracy"]
    algorithm: Literal["decision_tree", "random_forest", "svm"]
    model_name: str = Field(min_length=1, max_length=255, pattern=r"^[A-Za-z0-9_-]+$")
    version: str = Field(pattern=r"^\d{8}T\d{6}Z$")


def registry() -> dict[str, str]:
    if not ACTIVE_REGISTRY.exists():
        return {}
    try:
        value = json.loads(ACTIVE_REGISTRY.read_text(encoding="utf-8"))
        return value if isinstance(value, dict) else {}
    except (OSError, json.JSONDecodeError):
        return {}


def model_path(task: str) -> Path:
    configured = registry().get(task)
    if configured:
        return Path(configured)
    if task == "literacy":
        return MODEL_PATH
    raise HTTPException(status_code=503, detail=f"No active validated {task} model is configured.")


def load_package(task: str):
    path = model_path(task)
    if not path.exists():
        raise HTTPException(status_code=503, detail="Model file is not available. Train and activate a validated model first.")
    modified = path.stat().st_mtime
    cached = _packages.get(task)
    if cached is None or cached[0] != modified:
        # Registry paths are server-owned; arbitrary uploaded pickle files are never accepted.
        _packages[task] = (modified, joblib.load(path))
    return _packages[task][1]


def require_admin_token(token: str | None) -> None:
    if not ADMIN_TOKEN:
        raise HTTPException(status_code=503, detail="ML administration endpoints are disabled.")
    if not token or not secrets.compare_digest(token, ADMIN_TOKEN):
        raise HTTPException(status_code=403, detail="Invalid ML administration token.")


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "model_available": MODEL_PATH.exists(),
            "active_models": {task: Path(path).name for task, path in registry().items()},
            "admin_enabled": bool(ADMIN_TOKEN)}


@app.post("/predict", response_model=PredictionOutput, response_model_exclude_none=True)
def predict(payload: PredictionInput) -> PredictionOutput:
    package = load_package(payload.task)
    features = package["features"]
    frame = pd.DataFrame([{feature: getattr(payload, feature) for feature in features}])
    model = package["model"]
    probabilities = model.predict_proba(frame)[0]
    best_index = int(probabilities.argmax())
    prediction = str(model.classes_[best_index])
    confidence = float(probabilities[best_index])
    return PredictionOutput(
        prediction=prediction,
        confidence=round(confidence, 4),
        model_name=package.get("model_name", "reading_level_model"),
        evaluation=package.get("evaluation"),
    )


@app.post("/admin/evaluate")
def evaluate_models(payload: EvaluationInput, x_ml_admin_token: str | None = Header(default=None)) -> dict[str, Any]:
    require_admin_token(x_ml_admin_token)
    from training.model_comparison import InsufficientDataError, compare_models

    try:
        return compare_models(payload.records, payload.task, MODEL_ROOT, payload.minimum_rows)
    except InsufficientDataError as exc:
        return {"status": "insufficient_data", "task": payload.task, "message": str(exc)}


@app.post("/admin/activate")
def activate_model(payload: ActivationInput, x_ml_admin_token: str | None = Header(default=None)) -> dict[str, str]:
    require_admin_token(x_ml_admin_token)
    artifact = MODEL_ROOT / payload.task / payload.version / f"{payload.algorithm}.pkl"
    if not artifact.exists():
        raise HTTPException(status_code=409, detail="The selected validated model artifact is unavailable.")
    candidate = joblib.load(artifact)
    evaluation_method = candidate.get("evaluation", {}).get("method")
    if (candidate.get("task") != payload.task or candidate.get("algorithm") != payload.algorithm
            or candidate.get("version") != payload.version or candidate.get("model_name") != payload.model_name
            or evaluation_method not in {"learner_grouped_cross_validation", "held_out_test"}):
        raise HTTPException(status_code=409, detail="The selected artifact metadata does not match the registered model version.")
    active = registry()
    previous = active.get(payload.task, "")
    active[payload.task] = str(artifact)
    ACTIVE_REGISTRY.parent.mkdir(parents=True, exist_ok=True)
    temporary = ACTIVE_REGISTRY.with_suffix(".tmp")
    temporary.write_text(json.dumps(active, indent=2), encoding="utf-8")
    temporary.replace(ACTIVE_REGISTRY)
    _packages.pop(payload.task, None)
    return {"status": "activated", "task": payload.task, "algorithm": payload.algorithm,
            "model_name": payload.model_name, "previous_artifact": previous}
