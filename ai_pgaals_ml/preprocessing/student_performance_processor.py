from __future__ import annotations

import pandas as pd

from common import RAW_DIR, anonymous_code, ensure_final_columns, first_series, label_from_score, normalize_columns, numeric, read_tabular_files, write_processed


SOURCE = "student_performance"
FOLDER = RAW_DIR / "student+performance"


def _scale_grade(series: pd.Series) -> pd.Series:
    values = numeric(series)
    maximum = values.max(skipna=True)
    if pd.notna(maximum) and maximum <= 20:
        return values * 5
    return values


def _is_math_file(filename: str) -> bool:
    return "mat" in filename.lower()


def process() -> pd.DataFrame:
    rows: list[pd.DataFrame] = []
    for filename, raw in read_tabular_files(FOLDER):
        frame = normalize_columns(raw)
        final_grade = _scale_grade(first_series(frame, ["g3", "final_grade", "final_score", "score"]))
        previous_grade = _scale_grade(first_series(frame, ["g2", "second_period_grade", "previous_score"]))
        first_grade = _scale_grade(first_series(frame, ["g1", "first_period_grade", "baseline_score"]))
        is_math = _is_math_file(filename)

        output = pd.DataFrame({
            "student_code": [anonymous_code(SOURCE, index, f"{filename}:{index}") for index in range(len(frame))],
            "reading_score": pd.NA if is_math else final_grade,
            "reading_accuracy": pd.NA,
            "reading_speed": pd.NA,
            "comprehension_score": pd.NA if is_math else final_grade,
            "listening_score": pd.NA,
            "numeracy_score": final_grade if is_math else pd.NA,
            "assessment_attempts": 1,
            "previous_score": previous_grade.fillna(first_grade),
            "completion_time": pd.NA,
            "intervention_count": numeric(first_series(frame, ["failures", "intervention_count", "interventions"])).fillna(0),
            "reading_level_label": final_grade.map(label_from_score),
        })
        rows.append(output)

    return ensure_final_columns(pd.concat(rows, ignore_index=True)) if rows else ensure_final_columns(pd.DataFrame())


if __name__ == "__main__":
    path = write_processed(process(), "student_performance_processed.csv")
    print(f"Wrote {path}")
