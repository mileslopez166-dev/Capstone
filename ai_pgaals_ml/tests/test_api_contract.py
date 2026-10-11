import sys
import tempfile
import unittest
from unittest.mock import Mock, patch
import numpy as np
from pathlib import Path

from fastapi.testclient import TestClient

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from api import main
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
        self.assertNotIn("evaluation", response.json())

    def test_prediction_accepts_grouped_validation_metadata(self):
        model = Mock()
        model.classes_ = np.array(["Frustration", "Instructional"])
        model.predict_proba.return_value = np.array([[0.3, 0.7]])
        evaluation = {"method": "learner_grouped_cross_validation", "f1_macro": 0.81,
                      "folds": 3, "rows": 60, "learners": 20}
        package = {"model": model, "features": ["reading_score"], "evaluation": evaluation}
        with patch("api.main.load_package", return_value=package):
            response = TestClient(app).post("/predict", json={"reading_score": 70})
        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.json()["evaluation"], evaluation)

    def test_activation_uses_exact_server_owned_model_version(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            version = "20261011T120000Z"
            artifact = root / "literacy" / version / "decision_tree.pkl"
            artifact.parent.mkdir(parents=True)
            joblib_package = {
                "task": "literacy", "algorithm": "decision_tree", "version": version,
                "model_name": f"literacy_decision_tree_{version}",
                "evaluation": {"method": "learner_grouped_cross_validation"},
            }
            import joblib
            joblib.dump(joblib_package, artifact)
            registry_path = root / "active_models.json"
            with patch.object(main, "ADMIN_TOKEN", "secret"), patch.object(main, "MODEL_ROOT", root), \
                    patch.object(main, "ACTIVE_REGISTRY", registry_path):
                response = TestClient(app).post("/admin/activate", headers={"X-ML-Admin-Token": "secret"}, json={
                    "task": "literacy", "algorithm": "decision_tree", "version": version,
                    "model_name": f"literacy_decision_tree_{version}",
                })
                self.assertEqual(response.status_code, 200)
                self.assertEqual(response.json()["model_name"], f"literacy_decision_tree_{version}")
                self.assertEqual(main.registry()["literacy"], str(artifact))

                joblib_package["evaluation"] = {"method": "held_out_test", "accuracy": 0.8, "test_rows": 12}
                joblib.dump(joblib_package, artifact)
                winner = TestClient(app).post("/admin/activate", headers={"X-ML-Admin-Token": "secret"}, json={
                    "task": "literacy", "algorithm": "decision_tree", "version": version,
                    "model_name": f"literacy_decision_tree_{version}",
                })
                self.assertEqual(winner.status_code, 200)

                mismatch = TestClient(app).post("/admin/activate", headers={"X-ML-Admin-Token": "secret"}, json={
                    "task": "literacy", "algorithm": "decision_tree", "version": version,
                    "model_name": "literacy_decision_tree_wrong",
                })
                self.assertEqual(mismatch.status_code, 409)


if __name__ == "__main__":
    unittest.main()
