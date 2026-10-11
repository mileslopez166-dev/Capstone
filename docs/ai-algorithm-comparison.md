# AI-PGAALS Algorithm Comparison and Deployment

## Scope and safeguards

AI-PGAALS compares three proficiency classifiers and three learning-activity recommenders. The modules support instruction; they do not replace official assessment formulas. Existing Phil-IRI calculations, numeracy worksheet scoring, points, rewards, and historical submissions remain authoritative and are never rewritten during training or model activation.

Training uses only stored system records. No synthetic rows are inserted into production evaluations. When labels or interaction coverage are inadequate, the administrator dashboard reports **Insufficient Data**.

## Classification algorithms

| Algorithm | Purpose | Implementation |
|---|---|---|
| Decision Tree | Provides interpretable decision rules for proficiency classification. | Scikit-learn `DecisionTreeClassifier` with balanced class weights and tuned depth and leaf size. |
| Random Forest | Reduces the variance of a single tree through an ensemble. | Scikit-learn `RandomForestClassifier` with 200 trees, balanced subsample weights, and tuned depth and leaf size. |
| Support Vector Machine | Learns nonlinear decision boundaries between proficiency groups. | Scikit-learn `SVC` with probability estimates, balanced class weights, median imputation, standard scaling, and tuned regularization. |

Literacy targets are the valid Phil-IRI result saved for an assessment submission: **Frustration**, **Instructional**, or **Independent**. Group Screening Test results without an individual reading level are excluded.

Numeracy targets are intentionally unavailable until the project adopts documented ARAL-aligned definitions for **Beginning**, **Developing**, **Proficient**, and **Advanced**. Phil-IRI reading levels are never reused as numeracy labels.

### Available features

- Reading assessment score
- Reading accuracy
- Reading speed in words per minute, when recorded
- Comprehension score
- Listening-comprehension score
- Numeracy assessment score
- Attempt number
- Previous same-subject assessment score
- Completion time, when recorded
- Number of recorded interventions

Student identifiers are converted to keyed hashes before records leave Laravel. Names, email addresses, passwords, answer text, and story text are not sent to the training API. Missing measurements stay missing and are median-imputed inside each fitted pipeline.

## Training and evaluation method

1. Laravel exports labeled, anonymized submission features.
2. The FastAPI service validates the minimum row, class, and learner counts.
3. A reproducible 20% learner-grouped final holdout is created with seed 42.
4. The remaining learners are evaluated with stratified learner-grouped cross-validation, up to five folds.
5. Hyperparameters are selected inside the grouped folds using macro F1.
6. Accuracy, macro precision, macro recall, macro F1, standard deviation, and configuration are recorded for each candidate.
7. The validation macro F1 selects the candidate. The final holdout is used once for that selected candidate.
8. Final per-class metrics, confusion matrix, and prediction latency are stored.
9. Every artifact records task, algorithm, version, feature list, classes, and evaluation metadata.

The training service refuses evaluation with fewer than the configured minimum rows, fewer than two usable classes, fewer than four rows in a class, fewer than five learners, or no usable feature columns.

## Recommendation approaches

### Content-based filtering

Eligible, published activities are ranked by subject match, overlapping focus areas, current proficiency evidence, activity difficulty metadata, and non-completion. It works for a new learner without peer history.

### Collaborative filtering

User-based cosine similarity is calculated only from shared completed activities. Missing interactions are ignored and are not interpreted as dislikes. Candidate scores are weighted by similar learners' real assessment percentages. The approach requires at least 20 interactions across five learners and five activities; otherwise it falls back to content-based ranking.

### Hybrid recommendation

Normalized content and collaborative scores are combined as:

`hybrid score = alpha * content score + (1 - alpha) * collaborative score`

Validation compares alpha values 0.25, 0.50, and 0.75 and selects by NDCG@5. Sparse collaborative data trigger the same content-based fallback.

All approaches filter to published catalog activities assigned to the learner's section. Existing catalog rules also restrict numeracy entries to game-enabled ARAL worksheets.

## Recommendation evaluation

A relevant interaction is a completed activity with an official score of at least 75%. For each learner with sufficient history, the latest relevant interaction is held out. Ranking is produced from the remaining interactions, then evaluated with Precision@5, Recall@5, NDCG@5, catalog coverage, and average response time. Cold-start compatibility is reported separately.

These offline ranking metrics do not prove improved learning outcomes. Such a claim requires a separately designed learner intervention study.

## Administration and deployment

Open **Administration > AI Model Evaluation**. Training and recommendation evaluations are dispatched to the `ai` queue. The page shows statuses, data size, validation metrics, final holdout evidence, versions, active configurations, and history.

Only models registered as validated can be activated. Laravel sends the exact task, algorithm, model name, and version to the protected internal API. The API resolves a server-owned artifact path and verifies its embedded metadata before atomically changing the active registry. Activation records the administrator, timestamp, previous key, and new key. Rollback reactivates the previous validated configuration.

Set the same long random `ML_ADMIN_TOKEN` for the Laravel and ML containers. The ML API is exposed only to the private Compose network. Do not accept uploaded pickle files.

After deployment, run:

```bash
php artisan migrate --force
php artisan queue:restart
```

Dokploy should deploy both the `queue` and `ml-api` services from `compose.yaml`. The `ml_models` volume preserves trained artifacts and the active registry between deployments.

## Operating procedure

1. Confirm enough real labeled submissions exist.
2. Open **AI Model Evaluation** as an administrator.
3. Select **Run Literacy** or **Run Numeracy**.
4. Keep the queue worker running and wait for the status to leave Queued or Training.
5. Review validation macro F1, class metrics, final holdout metrics, confusion matrix, dataset provenance, and limitations.
6. Activate only a validated model whose evidence is acceptable for the capstone protocol.
7. Evaluate recommendation approaches separately and activate an evaluated approach.
8. Use **Restore previous** if an active configuration must be rolled back.

Normal submissions continue to save official scores when the ML service is disabled, unavailable, or has no active model. Recommendation generation is also best effort and cannot make submission saving fail.

## Chapter III template

### Research design

State that classification candidates use the same labeled task data and learner-grouped protocol. Explain that the final holdout is isolated from model selection. Describe why macro metrics are used when classes may be imbalanced.

### Data and features

| Item | Actual project definition |
|---|---|
| Population | Grade 6 learner submissions recorded in AI-PGAALS |
| Literacy target | Saved valid Phil-IRI proficiency level |
| Numeracy target | Pending approved ARAL proficiency definition |
| Grouping variable | Anonymous learner code |
| Missing data | Median imputation fitted within the pipeline |
| Random seed | 42 |
| Selection metric | Validation macro F1 |

### Recommendation protocol

Document the relevance threshold, temporal holdout rule, K=5, interaction minimums, alpha candidates, and automatic content-based fallback.

## Chapter IV template

Populate these tables only from an administrator evaluation record. Do not copy legacy full-dataset diagnostics into them.

### Classification comparison

| Subject | Model | Rows | Learners | Accuracy | Macro Precision | Macro Recall | Macro F1 | Status |
|---|---|---:|---:|---:|---:|---:|---:|---|
| Literacy | Decision Tree | Pending | Pending | Pending | Pending | Pending | Pending | Not evaluated |
| Literacy | Random Forest | Pending | Pending | Pending | Pending | Pending | Pending | Not evaluated |
| Literacy | SVM | Pending | Pending | Pending | Pending | Pending | Pending | Not evaluated |
| Numeracy | Decision Tree | Pending | Pending | Pending | Pending | Pending | Pending | Insufficient labeled targets |
| Numeracy | Random Forest | Pending | Pending | Pending | Pending | Pending | Pending | Insufficient labeled targets |
| Numeracy | SVM | Pending | Pending | Pending | Pending | Pending | Pending | Insufficient labeled targets |

### Selected model final holdout

| Subject | Selected model | Holdout rows | Holdout learners | Accuracy | Macro F1 | Latency/row | Version |
|---|---|---:|---:|---:|---:|---:|---|
| Literacy | Pending | Pending | Pending | Pending | Pending | Pending | Pending |
| Numeracy | Pending | Pending | Pending | Pending | Pending | Pending | Pending |

Include the saved confusion matrix and per-class precision, recall, F1, and support below the table.

### Recommendation comparison

| Approach | Interactions | Learners | Activities | Precision@5 | Recall@5 | NDCG@5 | Coverage | Time | Status |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---|
| Content-based | Pending | Pending | Pending | Pending | Pending | Pending | Pending | Pending | Not evaluated |
| Collaborative | Pending | Pending | Pending | Pending | Pending | Pending | Pending | Pending | Not evaluated |
| Hybrid | Pending | Pending | Pending | Pending | Pending | Pending | Pending | Pending | Not evaluated |

## Known limitations

- Numeracy classification cannot be trained until approved ARAL-aligned target labels are encoded and populated.
- Current activity metadata do not define explicit prerequisite graphs; eligibility uses publication, section, catalog, subject, focus area, difficulty, and completion data.
- Collaborative evaluation is unavailable when interaction history is sparse.
- Offline recommendation metrics do not establish causal learning improvement.
- Phil-IRI labels derived from assessment outcomes can be highly related to score features; grouped evaluation limits learner leakage but does not remove target-definition dependence.
- Model performance must be re-evaluated when the learner population, assessment design, or scoring rules change.
