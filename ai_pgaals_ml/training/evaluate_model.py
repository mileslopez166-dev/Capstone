from __future__ import annotations

from pathlib import Path

import joblib
import pandas as pd
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix


ROOT = Path(__file__).resolve().parents[1]
DATASET = ROOT / "processed" / "clean_dataset.csv"
MODEL_PATH = ROOT / "models" / "reading_level_model.pkl"
REPORT_PATH = ROOT / "processed" / "full_dataset_diagnostic_report.md"
IMPORTANCE_PATH = ROOT / "processed" / "feature_importance.csv"


def evaluate() -> dict:
    if not MODEL_PATH.exists():
        raise FileNotFoundError(f"Missing model: {MODEL_PATH}. Run training/train_model.py first.")
    if not DATASET.exists():
        raise FileNotFoundError(f"Missing dataset: {DATASET}. Run preprocessing/merge_dataset.py first.")

    package = joblib.load(MODEL_PATH)
    model = package["model"]
    features = package["features"]
    frame = pd.read_csv(DATASET).dropna(subset=["reading_level_label"])
    x = frame[features]
    y = frame["reading_level_label"]
    predictions = model.predict(x)
    accuracy = accuracy_score(y, predictions)
    report = classification_report(y, predictions, zero_division=0)
    matrix = confusion_matrix(y, predictions, labels=package["classes"])

    classifier = model.named_steps["classifier"]
    pd.DataFrame({
        "feature": features,
        "importance": classifier.feature_importances_,
    }).sort_values("importance", ascending=False).to_csv(IMPORTANCE_PATH, index=False)

    REPORT_PATH.write_text(
        "# Full Dataset Diagnostic Report\n\n"
        "Includes training rows. This is NOT held-out test accuracy.\n\n"
        f"Model: {package.get('model_name', 'reading_level_model')}\n\n"
        f"Rows evaluated: {len(frame)}\n\n"
        f"Accuracy: {accuracy:.4f}\n\n"
        "## Classification Report\n\n"
        f"```text\n{report}\n```\n\n"
        "## Confusion Matrix\n\n"
        f"Labels: {package['classes']}\n\n"
        f"```text\n{matrix}\n```\n\n"
        "## Teacher Decision Notice\n\n"
        "AI prediction is a support tool. Teachers should validate results before making educational decisions.\n",
        encoding="utf-8",
    )
    return {"accuracy": accuracy, "rows": len(frame)}


if __name__ == "__main__":
    print(evaluate())
