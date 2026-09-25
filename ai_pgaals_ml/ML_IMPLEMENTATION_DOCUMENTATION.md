# AI-PGAALS ML Implementation Documentation

## Purpose

The ML component predicts a support-level reading classification:

- Independent
- Instructional
- Frustration

It is a teacher support signal only. It does not replace Phil-IRI scoring, official assessment computation, or teacher judgment.

## Dataset Sources

Planned sources:

- DepEd CRLA 2025-2026 dataset
- DepEd NAT Grade 6 dataset
- IEA LaNA 2023 dataset
- student performance dataset from `student+performance`
- Future anonymized AI-PGAALS MySQL assessment exports

## Feature Selection

The model uses tabular educational indicators:

- reading score
- reading accuracy
- reading speed
- comprehension score
- listening score
- numeracy score
- assessment attempts
- previous score
- completion time
- intervention count

No student names, emails, passwords, or private profile fields are used.

## Data Preprocessing

The preprocessing scripts convert raw CRLA, NAT, LaNA, student performance, and AI-PGAALS-style CSV data into a shared format. Missing columns are filled with blank values, duplicate rows are removed, numeric columns are converted, and labels are created with transparent rules documented in `label_mapping.md`.

The file reader scans nested folders and supports CSV, Excel, SPSS `.sav`, and ZIP files containing CSV/Excel files.

## Model Selection

The first model is `RandomForestClassifier(n_estimators=100, random_state=42)`.

Reasons:

- Works well for tabular data
- Handles non-linear relationships
- Provides feature importance
- Easier to explain for a capstone system

## Training Process

Run:

```bash
cd ai_pgaals_ml
python preprocessing/crla_processor.py
python preprocessing/nat_processor.py
python preprocessing/lana_processor.py
python preprocessing/student_performance_processor.py
python preprocessing/merge_dataset.py
python training/train_model.py
python training/evaluate_model.py
```

Current baseline: 44,497 cleaned training rows. The model uses only feature columns with observed values, so empty columns such as reading speed can be added later without breaking training.

## API

Run:

```bash
uvicorn api.main:app --reload --host 127.0.0.1 --port 8001
```

Endpoint:

`POST /predict`

## Laravel Integration

Laravel stores predictions in `ml_predictions`. ML is disabled by default and becomes active only when `ML_PREDICTIONS_ENABLED=true` and the FastAPI URL is configured.

## Evaluation Metrics

The evaluation report includes:

- accuracy
- precision
- recall
- F1-score
- confusion matrix
- feature importance

## Limitations

- Prototype labels from public datasets are not official Phil-IRI labels.
- Some baseline labels are derived from score thresholds, so very high accuracy should be treated as a working baseline rather than proof of real-world performance.
- Predictions can be wrong.
- The model needs local validation before being used in real teacher workflows.
- Teacher review remains required.

## Privacy Protection

The training and API payloads must use anonymous identifiers only. The Laravel prediction API payload contains numeric assessment features and does not include student names or emails.
