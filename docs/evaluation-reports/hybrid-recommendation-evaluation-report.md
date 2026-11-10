# Recommendation Algorithm Evaluation Report

Historical snapshot: these results came from the supplied screenshot before
the recommendation-scoring correction. Rerun evaluation on the live server
after deployment and replace these values before presenting them as current.

Algorithm: Hybrid Recommendation

Status: Evaluated

Interactions: 122

Distinct learners: 30

Eligible activities: 16

Held-out interactions: 18

Selected alpha: 0.25

Cold-start behavior: Supported through Content-Based fallback

## Ranking Report

```text
Evaluation metric                 Value      Percentage

Precision@5                      0.1000           10.0%
Recall@5                         0.5000           50.0%
NDCG@5                           0.3210           32.1%
Catalog coverage                 0.8130           81.3%
Content fallback rate            0.0560            5.6%

Average response time         11.03 ms
Selected alpha                    0.25
```

## Hybrid Recommendation Formula

```text
Hybrid Score = alpha(Normalized Content Score)
             + (1 - alpha)(Normalized Collaborative Score)

Hybrid Score = 0.25(Normalized Content Score)
             + 0.75(Normalized Collaborative Score)
```

## Evaluation Calculations

```text
Precision@5 = Hits / (Holdouts * 5)
            = 9 / (18 * 5)
            = 9 / 90
            = 0.100 or 10.0%

Recall@5    = Hits / Holdouts
            = 9 / 18
            = 0.500 or 50.0%

NDCG@5      = Mean of (DCG@5 / Ideal DCG@5) across holdouts
            = Reported result: 0.321 or 32.1%

Coverage    = Unique Recommended Activities / Eligible Activities
            = 13 / 16
            = 0.813 or 81.3%

Fallback    = Content-Based Fallbacks / Holdouts
            = 1 / 18
            = 0.056 or 5.6%
```

## Note

Source: supplied Hybrid Recommendation evaluation screenshot. Decimal
values reflect the displayed percentages and are rounded. The hit,
coverage, and fallback counts above are inferred from the reported rates
and evaluation totals. NDCG cannot be recalculated without the saved ranks.

Hybrid Recommendation uses ranking metrics rather than classification Accuracy or F1 Score. It combines Content-Based and Collaborative evidence and falls back to Content-Based Filtering when collaborative information is insufficient.

Offline ranking results measure recommendation quality but do not independently prove improved student learning outcomes.

Evaluated: November 8, 2026, 2:24 PM
