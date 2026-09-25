import sys
import tempfile
import unittest
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "preprocessing"))

import pandas as pd

from common import FINAL_COLUMNS, ensure_final_columns, label_from_score, label_from_text, read_tabular_files


class PreprocessingTest(unittest.TestCase):
    def test_label_from_score(self):
        self.assertEqual(label_from_score(95), "Independent")
        self.assertEqual(label_from_score(75), "Instructional")
        self.assertEqual(label_from_score(40), "Frustration")

    def test_label_from_text(self):
        self.assertEqual(label_from_text("Advanced"), "Independent")
        self.assertEqual(label_from_text("Developing"), "Instructional")
        self.assertEqual(label_from_text("Below Basic"), "Frustration")

    def test_ensure_final_columns(self):
        frame = ensure_final_columns(pd.DataFrame({"student_code": ["student_001"], "reading_score": [90]}))
        self.assertEqual(list(frame.columns), FINAL_COLUMNS)
        self.assertEqual(frame.loc[0, "reading_score"], 90)

    def test_read_tabular_files_reads_csv_inside_zip(self):
        with tempfile.TemporaryDirectory() as directory:
            zip_path = Path(directory) / "student.zip"
            with zipfile.ZipFile(zip_path, "w") as archive:
                archive.writestr("student-mat.csv", "G1;G2;G3\n10;12;14\n")

            files = read_tabular_files(Path(directory))

        self.assertEqual(len(files), 1)
        self.assertEqual(files[0][1].loc[0, "G3"], 14)


if __name__ == "__main__":
    unittest.main()
