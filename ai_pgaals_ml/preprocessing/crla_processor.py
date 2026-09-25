from __future__ import annotations

import pandas as pd

from common import RAW_DIR, anonymous_code, ensure_final_columns, first_series, label_from_score, label_from_text, normalize_columns, numeric, read_tabular_files, write_processed


SOURCE = "CRLA"
FOLDER = RAW_DIR / SOURCE


def _percent(series: pd.Series) -> pd.Series:
    values = numeric(series)
    maximum = values.max(skipna=True)
    if pd.notna(maximum) and maximum > 1:
        return values / 100
    return values


def _weighted_crla_score(frame: pd.DataFrame) -> pd.Series:
    low = _percent(first_series(frame, ["school_low_emerging_percent"]))
    high = _percent(first_series(frame, ["school_high_emerging_percent"]))
    developing = _percent(first_series(frame, ["school_developing_percent"]))
    transitioning = _percent(first_series(frame, ["school_transitioning_percent"]))
    at_grade = _percent(first_series(frame, ["school_at_grade_level_percent"]))
    available = pd.concat([low, high, developing, transitioning, at_grade], axis=1)

    score = (
        low.fillna(0) * 20
        + high.fillna(0) * 40
        + developing.fillna(0) * 60
        + transitioning.fillna(0) * 80
        + at_grade.fillna(0) * 95
    )
    return score.mask(available.notna().sum(axis=1).eq(0), pd.NA)


def process() -> pd.DataFrame:
    rows: list[pd.DataFrame] = []
    for filename, raw in read_tabular_files(FOLDER):
        frame = normalize_columns(raw)
        reading_score = numeric(first_series(frame, ["reading_score", "literacy_score", "crla_score", "score", "total_score", "raw_score"]))
        reading_score = reading_score.fillna(_weighted_crla_score(frame))
        comprehension = numeric(first_series(frame, ["comprehension_score", "reading_comprehension", "comprehension", "english_score"]))
        label_source = first_series(frame, ["reading_level_label", "reading_level", "proficiency_level", "assessment_category", "category", "level"])
        identifier = first_series(frame, ["student_id", "learner_id", "student_code", "id"])

        output = pd.DataFrame({
            "student_code": [anonymous_code(SOURCE, index, identifier.iloc[index] if index in identifier.index else None) for index in range(len(frame))],
            "reading_score": reading_score,
            "reading_accuracy": numeric(first_series(frame, ["reading_accuracy", "accuracy", "word_reading_accuracy"])),
            "reading_speed": numeric(first_series(frame, ["reading_speed", "wpm", "words_per_minute"])),
            "comprehension_score": comprehension.fillna(reading_score),
            "listening_score": numeric(first_series(frame, ["listening_score", "listening", "listening_comprehension"])),
            "numeracy_score": numeric(first_series(frame, ["numeracy_score", "math_score", "mathematics_score"])),
            "assessment_attempts": numeric(first_series(frame, ["assessment_attempts", "attempts"])).fillna(1),
            "previous_score": numeric(first_series(frame, ["previous_score", "prior_score"])),
            "completion_time": numeric(first_series(frame, ["completion_time", "time_seconds", "duration_seconds"])),
            "intervention_count": numeric(first_series(frame, ["intervention_count", "interventions"])).fillna(0),
            "reading_level_label": label_source.map(label_from_text),
        })
        output["reading_level_label"] = output["reading_level_label"].fillna(output["comprehension_score"].map(label_from_score))
        rows.append(output)

    return ensure_final_columns(pd.concat(rows, ignore_index=True)) if rows else ensure_final_columns(pd.DataFrame())


if __name__ == "__main__":
    path = write_processed(process(), "crla_processed.csv")
    print(f"Wrote {path}")
