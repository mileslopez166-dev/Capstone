# Model Evaluation Report

Model: reading_level_random_forest_v1

Rows evaluated: 44497

Accuracy: 0.9998

## Classification Report

```text
               precision    recall  f1-score   support

  Frustration       1.00      1.00      1.00     27341
  Independent       1.00      1.00      1.00      2780
Instructional       1.00      1.00      1.00     14376

     accuracy                           1.00     44497
    macro avg       1.00      1.00      1.00     44497
 weighted avg       1.00      1.00      1.00     44497

```

## Confusion Matrix

Labels: ['Frustration', 'Independent', 'Instructional']

```text
[[27340     0     1]
 [    0  2780     0]
 [    5     1 14370]]
```

## Teacher Decision Notice

AI prediction is a support tool. Teachers should validate results before making educational decisions.
