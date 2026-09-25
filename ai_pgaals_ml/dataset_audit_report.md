# Dataset Audit Report

Generated for the AI-PGAALS ML baseline.

## Current Repository Audit

The local baseline training run used source datasets placed in:

- `ai_pgaals_ml/datasets/raw/CRLA/`
- `ai_pgaals_ml/datasets/raw/NAT/`
- `ai_pgaals_ml/datasets/raw/LaNA/`
- `ai_pgaals_ml/datasets/raw/student+performance/`

The preprocessing scripts scan subfolders recursively and can read CSV, Excel, SPSS `.sav` files when enabled, and ZIP files containing CSV/Excel files.
Raw source datasets are intentionally ignored by Git. Keep them local and regenerate processed CSV files when retraining.

Current processed baseline:

| Processed File | Rows | Usable Labeled Rows |
| --- | ---: | ---: |
| `crla_processed.csv` | 6,121 | 6,121 |
| `nat_processed.csv` | 6,636 | 6,636 |
| `lana_processed.csv` | 68,282 | 61,392 |
| `student_performance_processed.csv` | 1,044 | 1,044 |
| `clean_dataset.csv` | 44,497 | 44,497 |

## Expected Dataset Review

| Dataset | Expected Formats | What To Audit | Selected ML Features |
| --- | --- | --- | --- |
| DepEd CRLA 2025-2026 | CSV, XLSX | literacy indicators, reading performance, assessment category columns | `reading_score`, `reading_accuracy`, `comprehension_score`, `reading_level_label` |
| DepEd NAT Grade 6 | CSV, XLSX | grade level, English score, Math score, achievement level | `reading_score`, `numeracy_score`, `reading_level_label` |
| IEA LaNA 2023 | SAV, CSV | reading achievement, mathematics achievement, student factor variables | `reading_score`, `numeracy_score`, `reading_level_label` |
| Student Performance | ZIP, CSV | final grade, previous grade, failures/intervention proxy | `reading_score`, `numeracy_score`, `previous_score`, `intervention_count`, `reading_level_label` |
| AI-PGAALS MySQL export | CSV | anonymized submission metrics | all final feature columns |

## Required Final Columns

- `student_code`
- `reading_score`
- `reading_accuracy`
- `reading_speed`
- `comprehension_score`
- `listening_score`
- `numeracy_score`
- `assessment_attempts`
- `previous_score`
- `completion_time`
- `intervention_count`
- `reading_level_label`

## Privacy Rule

Do not include names, emails, addresses, photos, credentials, or classroom-identifying notes. Use anonymous `student_code` values only.
