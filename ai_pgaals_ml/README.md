# AI-PGAALS ML Workspace

This folder contains the explainable ML support pipeline for AI-PGAALS.

## 1. Install Dependencies

```bash
cd ai_pgaals_ml
python -m pip install -r requirements.txt
```

## 2. Add Datasets

Place files here:

- CRLA files: `datasets/raw/CRLA/`
- NAT files: `datasets/raw/NAT/`
- LaNA SPSS or CSV files: `datasets/raw/LaNA/`
- Student performance ZIP/CSV files: `datasets/raw/student+performance/`

Do not place files with student names, emails, passwords, addresses, or other sensitive personal data.

The processors scan subfolders recursively. ZIP files with CSV or Excel files are supported.
Raw dataset files are kept local and ignored by Git to avoid bloating the repository or publishing source data.

## 3. Process Data

```bash
python preprocessing/crla_processor.py
python preprocessing/nat_processor.py
python preprocessing/lana_processor.py
python preprocessing/student_performance_processor.py
python preprocessing/merge_dataset.py
```

Output:

- `processed/ai_pgaals_training_dataset.csv`
- `processed/clean_dataset.csv`

## 4. Train And Evaluate

```bash
python training/train_model.py
python training/evaluate_model.py
```

Output:

- `models/reading_level_model.pkl`
- `processed/model_evaluation_report.md`
- `processed/feature_importance.csv`

Current baseline model: 44,497 cleaned training rows from CRLA, NAT, LaNA, and student performance data.
Trained `.pkl` model files are kept local and ignored by Git. Run the training command again on another device after adding the local datasets.

## 5. Run API

```bash
uvicorn api.main:app --reload --host 127.0.0.1 --port 8001
```

Laravel integration is disabled by default. Enable it in `.env` only after the model is trained:

```env
ML_PREDICTIONS_ENABLED=true
ML_PREDICTION_API_URL=http://127.0.0.1:8001
```
