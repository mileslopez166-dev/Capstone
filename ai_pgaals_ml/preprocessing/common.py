from __future__ import annotations

import hashlib
import io
import re
import zipfile
from pathlib import Path
from typing import Iterable

import pandas as pd


ROOT = Path(__file__).resolve().parents[1]
RAW_DIR = ROOT / "datasets" / "raw"
PROCESSED_DIR = ROOT / "processed"

FINAL_COLUMNS = [
    "student_code",
    "reading_score",
    "reading_accuracy",
    "reading_speed",
    "comprehension_score",
    "listening_score",
    "numeracy_score",
    "assessment_attempts",
    "previous_score",
    "completion_time",
    "intervention_count",
    "reading_level_label",
]

NUMERIC_COLUMNS = [column for column in FINAL_COLUMNS if column not in {"student_code", "reading_level_label"}]
LABELS = ["Independent", "Instructional", "Frustration"]


def normalize_column_name(name: object) -> str:
    value = re.sub(r"[^a-z0-9]+", "_", str(name).strip().lower())
    return value.strip("_")


def normalize_columns(frame: pd.DataFrame) -> pd.DataFrame:
    frame = frame.copy()
    frame.columns = [normalize_column_name(column) for column in frame.columns]
    return frame


def _read_csv(source) -> pd.DataFrame:
    return pd.read_csv(source, sep=None, engine="python")


def read_tabular_files(folder: Path, allow_sav: bool = False) -> list[tuple[str, pd.DataFrame]]:
    files: list[tuple[str, pd.DataFrame]] = []
    for path in sorted(folder.rglob("*")):
        if path.name.startswith(".") or path.is_dir():
            continue
        suffix = path.suffix.lower()
        if suffix == ".csv":
            files.append((str(path.relative_to(folder)), _read_csv(path)))
        elif suffix in {".xlsx", ".xls"}:
            files.append((str(path.relative_to(folder)), pd.read_excel(path)))
        elif suffix == ".zip":
            with zipfile.ZipFile(path) as archive:
                for member in sorted(archive.namelist()):
                    member_suffix = Path(member).suffix.lower()
                    if member.endswith("/") or Path(member).name.startswith("."):
                        continue
                    if member_suffix == ".csv":
                        with archive.open(member) as handle:
                            files.append((f"{path.name}:{member}", _read_csv(handle)))
                    elif member_suffix in {".xlsx", ".xls"}:
                        with archive.open(member) as handle:
                            files.append((f"{path.name}:{member}", pd.read_excel(io.BytesIO(handle.read()))))
        elif allow_sav and suffix == ".sav":
            try:
                import pyreadstat
            except ImportError as exc:
                raise RuntimeError("Install pyreadstat to process SPSS .sav files.") from exc
            frame, _metadata = pyreadstat.read_sav(path)
            files.append((str(path.relative_to(folder)), frame))
    return files


def first_series(frame: pd.DataFrame, candidates: Iterable[str]) -> pd.Series:
    normalized = {normalize_column_name(candidate) for candidate in candidates}
    for column in frame.columns:
        if column in normalized:
            return frame[column]
    return pd.Series([pd.NA] * len(frame), index=frame.index)


def numeric(series: pd.Series) -> pd.Series:
    return pd.to_numeric(series, errors="coerce")


def label_from_score(score: object) -> object:
    value = pd.to_numeric(pd.Series([score]), errors="coerce").iloc[0]
    if pd.isna(value):
        return pd.NA
    if value >= 80:
        return "Independent"
    if value >= 59:
        return "Instructional"
    return "Frustration"


def label_from_text(value: object) -> object:
    text = str(value).strip().lower()
    if not text or text == "nan":
        return pd.NA
    if any(token in text for token in ["independent", "advanced", "proficient", "exceeds", "high"]):
        return "Independent"
    if any(token in text for token in ["instructional", "developing", "satisfactory", "medium", "near"]):
        return "Instructional"
    if any(token in text for token in ["frustration", "beginning", "below", "low", "support"]):
        return "Frustration"
    return pd.NA


def anonymous_code(source: str, row_number: int, raw_identifier: object = None) -> str:
    material = f"{source}|{row_number}|{raw_identifier if raw_identifier is not None else ''}"
    digest = hashlib.sha256(material.encode("utf-8")).hexdigest()[:12]
    return f"student_{digest}"


def ensure_final_columns(frame: pd.DataFrame) -> pd.DataFrame:
    frame = frame.copy()
    for column in FINAL_COLUMNS:
        if column not in frame.columns:
            frame[column] = pd.NA
    frame = frame[FINAL_COLUMNS]
    for column in NUMERIC_COLUMNS:
        frame[column] = numeric(frame[column])
    return frame


def write_processed(frame: pd.DataFrame, filename: str) -> Path:
    PROCESSED_DIR.mkdir(parents=True, exist_ok=True)
    output = PROCESSED_DIR / filename
    ensure_final_columns(frame).to_csv(output, index=False)
    return output
