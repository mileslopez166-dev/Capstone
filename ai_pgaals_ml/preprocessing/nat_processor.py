from __future__ import annotations

import pandas as pd

from common import RAW_DIR, anonymous_code, ensure_final_columns, first_series, label_from_score, label_from_text, normalize_columns, numeric, read_tabular_files, write_processed


SOURCE = "NAT"
FOLDER = RAW_DIR / SOURCE


def process() -> pd.DataFrame:
    rows: list[pd.DataFrame] = []
    for filename, raw in read_tabular_files(FOLDER):
        frame = normalize_columns(raw)
        english_score = numeric(first_series(frame, ["english_score", "english_mps", "reading_score", "literacy_score", "nat_english", "language_score"]))
        math_score = numeric(first_series(frame, ["mathematics_score", "math_score", "math_mps", "numeracy_score", "nat_math"]))
        achievement = first_series(frame, ["achievement_level", "english_achievement", "proficiency_level", "level"])
        identifier = first_series(frame, ["student_id", "learner_id", "student_code", "id"])

        output = pd.DataFrame({
            "student_code": [anonymous_code(SOURCE, index, identifier.iloc[index] if index in identifier.index else None) for index in range(len(frame))],
            "reading_score": english_score,
            "reading_accuracy": numeric(first_series(frame, ["reading_accuracy", "accuracy"])),
            "reading_speed": numeric(first_series(frame, ["reading_speed", "wpm", "words_per_minute"])),
            "comprehension_score": numeric(first_series(frame, ["comprehension_score", "reading_comprehension"])).fillna(english_score),
            "listening_score": numeric(first_series(frame, ["listening_score", "listening_comprehension"])),
            "numeracy_score": math_score,
            "assessment_attempts": numeric(first_series(frame, ["assessment_attempts", "attempts"])).fillna(1),
            "previous_score": numeric(first_series(frame, ["previous_score", "prior_score"])),
            "completion_time": numeric(first_series(frame, ["completion_time", "time_seconds", "duration_seconds"])),
            "intervention_count": numeric(first_series(frame, ["intervention_count", "interventions"])).fillna(0),
            "reading_level_label": achievement.map(label_from_text),
        })
        output["reading_level_label"] = output["reading_level_label"].fillna(output["comprehension_score"].map(label_from_score))
        rows.append(output)

    return ensure_final_columns(pd.concat(rows, ignore_index=True)) if rows else ensure_final_columns(pd.DataFrame())


if __name__ == "__main__":
    path = write_processed(process(), "nat_processed.csv")
    print(f"Wrote {path}")
