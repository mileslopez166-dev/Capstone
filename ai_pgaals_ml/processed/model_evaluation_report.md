# Model Evaluation Report

Model: reading_level_random_forest_v1

Rows: 44497

Features used: reading_score, comprehension_score, numeracy_score, assessment_attempts, previous_score, intervention_count

Ignored empty features: reading_accuracy, reading_speed, listening_score, completion_time

Held-out test rows: 8900

Held-out test accuracy: 0.9996

## Classification Report

```text
               precision    recall  f1-score   support

  Frustration       1.00      1.00      1.00      5469
  Independent       1.00      1.00      1.00       556
Instructional       1.00      1.00      1.00      2875

     accuracy                           1.00      8900
    macro avg       1.00      1.00      1.00      8900
 weighted avg       1.00      1.00      1.00      8900

```

## Confusion Matrix

Labels: ['Frustration', 'Independent', 'Instructional']

```text
[[5468    0    1]
 [   0  556    0]
 [   2    1 2872]]
```

## Note

This model is a support tool only. It does not replace Phil-IRI scoring or teacher judgment.
