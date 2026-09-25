from __future__ import annotations

import pandas as pd

from common import RAW_DIR, anonymous_code, ensure_final_columns, first_series, label_from_score, label_from_text, normalize_columns, numeric, read_tabular_files, write_processed


SOURCE = "LaNA"
FOLDER = RAW_DIR / SOURCE


def _mean_columns(frame: pd.DataFrame, candidates: list[str]) -> pd.Series:
    columns = [candidate for candidate in candidates if candidate in frame.columns]
    if not columns:
        return pd.Series([pd.NA] * len(frame), index=frame.index)
    return pd.concat([numeric(frame[column]) for column in columns], axis=1).mean(axis=1)


def _scale_lana_score(series: pd.Series) -> pd.Series:
    values = numeric(series)
    maximum = values.max(skipna=True)
    if pd.notna(maximum) and maximum > 100:
        return (values / 625 * 100).clip(lower=0, upper=100)
    return values


def process() -> pd.DataFrame:
    rows: list[pd.DataFrame] = []
    for filename, raw in read_tabular_files(FOLDER, allow_sav=True):
        frame = normalize_columns(raw)
        reading_score = numeric(first_series(frame, ["reading_score", "reading_achievement", "pvread", "literacy_score", "read"]))
        reading_plausible = _mean_columns(frame, ["asrrea01", "asrrea02", "asrrea03", "asrrea04", "asrrea05"])
        reading_score = _scale_lana_score(reading_score.fillna(reading_plausible))
        math_score = numeric(first_series(frame, ["mathematics_score", "math_score", "math_achievement", "pvmath", "numeracy_score"]))
        math_plausible = _mean_columns(frame, ["asmmat01", "asmmat02", "asmmat03", "asmmat04", "asmmat05"])
        math_score = _scale_lana_score(math_score.fillna(math_plausible))
        reading_level = first_series(frame, ["reading_level_label", "reading_level", "achievement_level", "proficiency_level"])
        identifier = first_series(frame, ["student_id", "student_code", "idstudent", "idstud", "id"])

        output = pd.DataFrame({
            "student_code": [anonymous_code(SOURCE, index, identifier.iloc[index] if index in identifier.index else None) for index in range(len(frame))],
            "reading_score": reading_score,
            "reading_accuracy": numeric(first_series(frame, ["reading_accuracy", "accuracy"])),
            "reading_speed": numeric(first_series(frame, ["reading_speed", "wpm", "words_per_minute"])),
            "comprehension_score": numeric(first_series(frame, ["comprehension_score", "reading_comprehension"])).fillna(reading_score),
            "listening_score": numeric(first_series(frame, ["listening_score", "listening_comprehension"])),
            "numeracy_score": math_score,
            "assessment_attempts": numeric(first_series(frame, ["assessment_attempts", "attempts"])).fillna(1),
            "previous_score": numeric(first_series(frame, ["previous_score", "prior_score"])),
            "completion_time": numeric(first_series(frame, ["completion_time", "time_seconds", "duration_seconds"])),
            "intervention_count": numeric(first_series(frame, ["intervention_count", "interventions"])).fillna(0),
            "reading_level_label": reading_level.map(label_from_text),
        })
        output["reading_level_label"] = output["reading_level_label"].fillna(output["comprehension_score"].map(label_from_score))
        rows.append(output)

    return ensure_final_columns(pd.concat(rows, ignore_index=True)) if rows else ensure_final_columns(pd.DataFrame())


if __name__ == "__main__":
    path = write_processed(process(), "lana_processed.csv")
    print(f"Wrote {path}")
