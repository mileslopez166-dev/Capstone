# Model Evaluation Report

Model: Decision Tree

Subject: Literacy

Model version: 20261109T082917Z

Status: Validated - Recommended

Rows: 88

Distinct learners: 23

Development rows: 74

Held-out rows: 14

Class distribution: Frustration: 35, Instructional: 21, Independent: 32

Features used: reading_score, reading_accuracy, reading_speed,
comprehension_score, assessment_attempts, previous_score,
completion_time, intervention_count

Processing method: Median imputation, balanced class weights,
and tuned tree depth and leaf size

Validation method: 5-fold stratified learner-grouped cross-validation

Selected parameters: max_depth=3, min_samples_leaf=3

Random seed: 42

Cross-validated accuracy: 0.974

Cross-validated macro F1: 0.976

## Classification Report - Cross-Validation

```text
                       precision      recall    f1-score

           macro avg      0.9720      0.9820      0.9760

            accuracy                              0.9740
     F1 standard dev                              0.0311

    validation folds                                   5
    development rows                                  74
```

Values are the reported cross-validation averages, not final test metrics.

## Dataset Class Distribution

```text
Class                    Dataset records

Frustration                           35
Instructional                         21
Independent                           32

Total                                 88
```

## Confusion Matrix

Not supplied in the screenshots. A completed matrix and per-class test
report require the saved Final Untouched Holdout results for Decision Tree.

## Evaluation Formulas

```text
Accuracy  = Correct Predictions / Total Predictions

Precision = True Positives / (True Positives + False Positives)

Recall    = True Positives / (True Positives + False Negatives)

F1 Score  = 2 * (Precision * Recall) / (Precision + Recall)

Macro F1  = (F1_Frustration + F1_Instructional + F1_Independent) / 3
```

## Note

Source: supplied Literacy Decision Tree evaluation screenshot. Dataset
record counts describe all 88 records; they are not test-set support counts.
Per-class metrics and weighted averages were not supplied.

This model is a support tool only. It does not replace official Phil-IRI scoring or teacher judgment.

Evaluated: November 9, 2026, 8:29 AM
