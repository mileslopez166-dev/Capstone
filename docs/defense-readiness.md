# Defense Readiness Checklist

Prepared: November 10, 2026. Target defense: November 11, 2026.

## Code corrections

| Area | Correction |
|---|---|
| Recommendation ranking | Calculated Content-Based, Collaborative, and Hybrid scores replace the original content score before sorting. |
| Recommendation explanations | Peer-based reasons survive in displayed and saved recommendations. |
| Evaluation history | Question placement reads only the supplied training interactions during evaluation. Held-out answers are excluded. |
| Retakes | Recommendation history chooses the best percentage rather than the largest question-point total. Unscored attempts are excluded. |
| Numeracy recommendations | Numeracy proficiency bands map to the internal question-difficulty bands without assigning a reading level. |
| Teacher dashboard | Literacy and Numeracy counts use the latest submitted subject result for each learner, scoped to that teacher's assessments. |
| Support badges | Overall-average flags say Priority support or Guided review instead of assigning a Phil-IRI reading level. |
| Regression tests | Both teacher dashboard layouts are checked independently. Oral-reading hidden fields are checked using HTML attributes. |

## Deploy to the live server

These source changes must be deployed before they affect the live site.
Local service availability does not establish the live server's status.

1. Back up the live database and retain the current working deployment.
2. Deploy the reviewed code to the application and queue services.
3. In the live **app** container terminal, run:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan queue:restart
```

4. Confirm the `queue` and `ml-api` services are running. The queue worker must process both `ai` and `default` queues.
5. Check the ML service from the live application container:

```bash
php artisan tinker --execute="dump(Illuminate\Support\Facades\Http::timeout(5)->get(config('ai_models.endpoint').'/health')->json());"
```

6. Confirm the response status is `ok`, administration is enabled if evaluation is needed, and the active model registry contains the expected Literacy and Numeracy models. If administration is disabled, configure the same private `ML_ADMIN_TOKEN` in Laravel and FastAPI and restart both services. Do not include the token in screenshots or the manuscript.
7. Keep existing validated models if they are already working. If a subject has no active model, use **Administration > AI Model Evaluation** to evaluate that subject, review its results, and activate the chosen validated version. Real labeled submissions are required; do not fabricate training records.

## Refresh recommendation evidence

1. Run the recommendation evaluation again after deployment.
2. Wait for its status to become Evaluated. Insufficient Data means that more real eligible interaction history is needed.
3. Review Precision@5, Recall@5, NDCG@5, coverage, response time, fallback rate, and selected alpha.
4. Activate the evaluated Hybrid approach if that is the approach selected for the project. Use its newly selected alpha; do not force the old value of 0.25.
5. Replace the old Section B screenshot and manuscript metrics with the new results. Prior identical metrics were produced before the ranking correction.

## Rehearse on the live site

Use authorized demo accounts and designated demo assessments.

| Role | Action | Expected outcome |
|---|---|---|
| Teacher | Create and publish a silent-reading assessment with questions | Assessment saves and becomes visible to the intended students. |
| Teacher | Create an oral-reading assessment without questions | It saves successfully. |
| Student | Submit oral reading with red-marked words | WRP uses the saved marks. Missing comprehension is hidden. |
| Student | Finish a numeracy worksheet/game | Score, answer review, proficiency, and rewards are saved. |
| Student | Open the result again and refresh | The same official result remains available. |
| Teacher | Open the student's report | Owned results match the student's recorded result. |
| Teacher | Open the dashboard | Literacy and Numeracy use separate level counts. |
| Student | Open recommendations | Eligible activities and explanations appear; sparse peer history uses content fallback. |
| Student | Ask the tutor a relevant question | A usable reply appears with the live provider configuration. |
| Student | Navigate while music is playing | Playback resumes from its saved position, subject to browser autoplay rules. |
| Teacher | Switch current/classic workspace | The selected layout has working navigation and a single shared footer. |
| Both | Use the actual defense laptop and a phone | Forms, publishing controls, menus, and result panels remain usable. |

## Documentation

Use the current live evaluation results in the manuscript. Validation metrics,
final holdout metrics, and student assessment scores describe different things.
Keep a copy of the presentation, current reports, and a short demonstration
recording available for the defense.
