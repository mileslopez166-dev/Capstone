from __future__ import annotations

import pandas as pd

from common import FINAL_COLUMNS, LABELS, NUMERIC_COLUMNS, PROCESSED_DIR, ensure_final_columns, label_from_score, numeric


SOURCE_FILES = [
    "crla_processed.csv",
    "nat_processed.csv",
    "lana_processed.csv",
    "student_performance_processed.csv",
    "ai_pgaals_export.csv",
]


def clean(frame: pd.DataFrame) -> pd.DataFrame:
    frame = ensure_final_columns(frame)
    frame = frame.drop_duplicates()
    for column in NUMERIC_COLUMNS:
        frame[column] = numeric(frame[column])
    frame["reading_level_label"] = frame["reading_level_label"].where(
        frame["reading_level_label"].isin(LABELS),
        frame["comprehension_score"].fillna(frame["reading_score"]).map(label_from_score),
    )
    frame = frame.dropna(subset=["reading_level_label"])
    return frame[FINAL_COLUMNS]


def merge() -> pd.DataFrame:
    frames = []
    for filename in SOURCE_FILES:
        path = PROCESSED_DIR / filename
        if path.exists():
            frames.append(pd.read_csv(path, low_memory=False))
    if not frames:
        return clean(pd.DataFrame(columns=FINAL_COLUMNS))
    return clean(pd.concat(frames, ignore_index=True))


if __name__ == "__main__":
    PROCESSED_DIR.mkdir(parents=True, exist_ok=True)
    dataset = merge()
    training_path = PROCESSED_DIR / "ai_pgaals_training_dataset.csv"
    clean_path = PROCESSED_DIR / "clean_dataset.csv"
    dataset.to_csv(training_path, index=False)
    dataset.to_csv(clean_path, index=False)
    print(f"Wrote {training_path}")
    print(f"Wrote {clean_path}")
    print(f"Rows: {len(dataset)}")
