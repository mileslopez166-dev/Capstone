import sys
import unittest
from unittest.mock import Mock, patch
import numpy as np
from pathlib import Path

from fastapi.testclient import TestClient

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from api.main import app


class ApiContractTest(unittest.TestCase):
    def test_health(self):
        response = TestClient(app).get("/health")
        self.assertEqual(response.status_code, 200)
        self.assertIn("model_available", response.json())

    def test_invalid_percentage_is_rejected_before_model_load(self):
        response = TestClient(app).post("/predict", json={"reading_accuracy": 150})
        self.assertEqual(response.status_code, 422)

    def test_prediction_exposes_only_packaged_test_metrics(self):
        model = Mock()
        model.classes_ = np.array(["Independent", "Instructional"])
        model.predict_proba.return_value = np.array([[0.11, 0.89]])
        evaluation = {"method": "held_out_test", "accuracy": 0.925, "test_rows": 80}
        package = {"model": model, "features": ["reading_score"], "evaluation": evaluation}
        with patch("api.main.load_package", return_value=package):
            response = TestClient(app).post("/predict", json={"reading_score": 75})
        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.json()["confidence"], 0.89)
        self.assertEqual(response.json()["evaluation"], evaluation)
        del package["evaluation"]
        with patch("api.main.load_package", return_value=package):
            response = TestClient(app).post("/predict", json={"reading_score": 75})
        self.assertIsNone(response.json()["evaluation"])


if __name__ == "__main__":
    unittest.main()
