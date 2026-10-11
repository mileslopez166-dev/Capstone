import sys
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import joblib
import pandas as pd

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from training import model_comparison


def records():
    rows = []
    levels = [(35, "Frustration"), (68, "Instructional"), (92, "Independent")]
    for score, label in levels:
        for learner in range(12):
            for attempt in range(2):
                rows.append({
                    "student_code": f"{label}-{learner}",
                    "proficiency_label": label,
                    "reading_score": score + attempt,
                    "comprehension_score": score,
                    "assessment_attempts": attempt + 1,
                    "previous_score": None if attempt == 0 else score - 2,
                })
    return rows


class ModelComparisonTest(unittest.TestCase):
    def test_candidates_include_all_required_algorithms(self):
        self.assertEqual(
            set(model_comparison.candidates()),
            {"decision_tree", "random_forest", "svm"},
        )

    def test_split_is_reproducible_and_keeps_learners_in_one_partition(self):
        frame, _ = model_comparison.prepare(records(), "literacy", 30)
        first_development, first_test = model_comparison.learner_group_split(frame)
        second_development, second_test = model_comparison.learner_group_split(frame)

        self.assertFalse(set(first_development.student_code) & set(first_test.student_code))
        self.assertEqual(first_development.index.tolist(), second_development.index.tolist())
        self.assertEqual(first_test.index.tolist(), second_test.index.tolist())

    def test_prepare_accepts_missing_optional_features_and_rejects_unlabeled_numeracy(self):
        frame, features = model_comparison.prepare(records(), "literacy", 30)
        self.assertIn("reading_score", features)
        self.assertTrue(frame["reading_speed"].isna().all())
        with self.assertRaises(model_comparison.InsufficientDataError):
            model_comparison.prepare([], "numeracy", 30)

    def test_comparison_trains_and_serializes_all_three_classifiers(self):
        reduced = {}
        for name, (estimator, parameters) in model_comparison.candidates().items():
            reduced[name] = (estimator, {key: [values[0]] for key, values in parameters.items()})

        with tempfile.TemporaryDirectory() as directory, patch.object(model_comparison, "candidates", return_value=reduced):
            result = model_comparison.compare_models(records(), "literacy", Path(directory), minimum=30)

            self.assertEqual(result["status"], "evaluated")
            self.assertEqual(set(result["models"]), {"decision_tree", "random_forest", "svm"})
            self.assertEqual(result["final_test_metrics"]["method"], "learner_grouped_final_holdout")
            for name, details in result["models"].items():
                self.assertEqual(details["status"], "validated")
                self.assertIn("f1_macro", details["validation_metrics"])
                package = joblib.load(details["artifact_path"])
                self.assertEqual(package["algorithm"], name)
                self.assertEqual(package["task"], "literacy")

            winner = result["recommended_algorithm"]
            winner_package = joblib.load(result["models"][winner]["artifact_path"])
            self.assertEqual(winner_package["evaluation"]["method"], "held_out_test")
            self.assertEqual(winner_package["evaluation"]["test_rows"], result["final_test_metrics"]["rows"])


if __name__ == "__main__":
    unittest.main()
