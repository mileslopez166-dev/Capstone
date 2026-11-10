# Model Evaluation Report

Model: Decision Tree

Subject: Numeracy

Model version: 20261109T145443Z

Status: Validated - Recommended

Rows: 52

Distinct learners: 27

Development rows: 38

Held-out rows: 14

Class distribution: Beginning: 36, Developing: 10, Proficient: 6

Features used: numeracy_score, assessment_attempts, previous_score, intervention_count

Processing method: Median imputation, balanced class weights,
and tuned tree depth and leaf size

Validation method: 5-fold stratified learner-grouped cross-validation

Selected parameters: max_depth=3, min_samples_leaf=1

Random seed: 42

Cross-validated accuracy: 0.971

Cross-validated macro F1: 0.927

## Classification Report - Cross-Validation

```text
                       precision      recall    f1-score

           macro avg      0.9220      0.9330      0.9270

            accuracy                              0.9710
     F1 standard dev                              0.1455

    validation folds                                   5
    development rows                                  38
```

Values are the reported cross-validation averages, not final test metrics.

## Dataset Class Distribution

```text
Class                    Dataset records

Beginning                             36
Developing                            10
Proficient                             6

Total                                 52
```

No Advanced records were shown in the supplied distribution.

## Confusion Matrix

Not supplied in the screenshots. A completed matrix and per-class test
report require the saved Final Untouched Holdout results for Decision Tree.

## Evaluation Formulas

```text
Accuracy  = Correct Predictions / Total Predictions

Precision = True Positives / (True Positives + False Positives)

Recall    = True Positives / (True Positives + False Negatives)

F1 Score  = 2 * (Precision * Recall) / (Precision + Recall)

Macro F1  = Sum of the F1 scores of represented levels
            / Number of represented levels
```

## Numeracy Proficiency Levels

```text
Beginning   = 0% to 59%
Developing  = 60% to 74%
Proficient  = 75% to 89%
Advanced    = 90% to 100%
```

## Note

Source: supplied Numeracy Decision Tree evaluation screenshot. Dataset
record counts describe all 52 records; they are not test-set support counts.
Per-class metrics and weighted averages were not supplied.

The 14.55% F1 standard deviation indicates variation between validation folds. The model reproduces project-defined numeracy score bands and does not independently prove learning improvement.

Evaluated: November 9, 2026, 2:54 PM
