from __future__ import annotations

from pathlib import Path
from typing import Literal, Optional

import joblib
import pandas as pd
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field


ROOT = Path(__file__).resolve().parents[1]
MODEL_PATH = ROOT / "models" / "reading_level_model.pkl"

app = FastAPI(title="AI-PGAALS ML API", version="1.0.0")
_package = None


class PredictionInput(BaseModel):
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
    method: Literal["held_out_test"]
    accuracy: float = Field(ge=0, le=1)
    test_rows: int = Field(gt=0)


class PredictionOutput(BaseModel):
    prediction: str
    confidence: float
    model_name: str
    evaluation: Optional[ModelEvaluation] = None


def load_package():
    global _package
    if _package is None:
        if not MODEL_PATH.exists():
            raise HTTPException(status_code=503, detail="Model file is not available. Train the model first.")
        _package = joblib.load(MODEL_PATH)
    return _package


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "model_available": MODEL_PATH.exists()}


@app.post("/predict", response_model=PredictionOutput)
def predict(payload: PredictionInput) -> PredictionOutput:
    package = load_package()
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
