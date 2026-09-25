import sys
import unittest
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


if __name__ == "__main__":
    unittest.main()
