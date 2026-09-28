import sys
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import joblib
import pandas as pd

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from training import train_model, evaluate_model


class TrainingMetricsTest(unittest.TestCase):
    def test_model_keeps_held_out_metrics_separate_from_full_dataset_diagnostics(self):
        rows = []
        for score, label in [(30, "Frustration"), (75, "Instructional"), (95, "Independent")]:
            for _ in range(10):
                row = dict.fromkeys(train_model.FEATURE_COLUMNS)
                row.update(reading_score=score, reading_level_label=label)
                rows.append(row)
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            paths = {"MODEL_PATH": root / "model.pkl", "REPORT_PATH": root / "report.md",
                     "IMPORTANCE_PATH": root / "importance.csv"}
            with patch.object(train_model, "load_dataset", return_value=pd.DataFrame(rows)), patch.multiple(train_model, **paths):
                result = train_model.train()
            package = joblib.load(paths["MODEL_PATH"])
            self.assertEqual(package["evaluation"], {
                "method": "held_out_test", "accuracy": result["accuracy"], "test_rows": 6,
            })
            self.assertIn("Held-out test rows: 6", paths["REPORT_PATH"].read_text())
        self.assertNotEqual(train_model.REPORT_PATH, evaluate_model.REPORT_PATH)
