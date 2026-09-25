# Literacy Scoring

Reference: [DepEd Phil-IRI Manual, 2018](https://lrmds.deped.gov.ph/detail/13908), Tables 7-8 and the Grade 6 group screening procedure.

- Teacher-authored activities are labelled **Phil-IRI-based**, not certified Phil-IRI tests or report-card grades.
- Comprehension: Independent >=80%, Instructional >=59%, otherwise Frustration. Oral word reading: Independent >=97%, Instructional >=90%, otherwise Frustration. Classifications use unrounded ratios; display uses two decimals.
- Oral profiles use word reading and comprehension when both are available; the lower level applies. The creating teacher can still correct miscues, passage word count, optional time, and orally administered comprehension when there were no online questions.
- Oral marking is red-only: click a word to mark or clear it, or toggle a whole sentence. Each red word occurrence counts once; punctuation-only tokens are excluded. Complete saved marks produce the word-reading percentage, `(words - red marks) / words * 100`, and prefill the teacher's miscue count. Missing/incomplete marks do not imply 100% accuracy. Old yellow marks are cleared when resuming, not converted to errors; previously saved scores are unchanged.
- Silent/listening results use comprehension only. WPM is descriptive, not a grading threshold. Zero does not automatically identify a non-reader.
- Group screening uses the 14/20 cutoff only for 20-item activities, never an inferred individual reading level.
- `assessment_submissions.phil_iri` stores versioned results and teacher review identity/time. Older records derive comprehension from saved counts; missing oral observations remain pending.
- Comprehension interpretations explain the saved comprehension level, not the overall oral reading level. Complete Independent, Instructional, and Frustration results also show a level-based practice recommendation: enrichment challenge, guided practice, or remediation support. They appear on completion, student review, dashboards, and teacher records, including older results without stored interpretation text. Group screening uses its own cutoff explanation; an unrecorded score is not interpreted as zero. The guidance is plain-language application copy, not a diagnostic conclusion or a quotation from the manual.
- EXP, retries, leaderboard points, and numeracy are unchanged. Run `php artisan migrate` when deploying.

Tests: `php artisan test --filter=PhilIri --do-not-cache-result`.

## Teacher Action Plans

Each completed assessment result has a collapsible Teacher Action Plan in the teacher's student profile and individual report. Plans store the intervention (Enrichment, Guided Practice, or Remediation), required action notes, an optional follow-up date, and a Planned / In Progress / Done status. Past follow-up dates are marked overdue until the plan is done.

For literacy results, the initial intervention selection follows the existing reading-level recommendation. The teacher can change it; no plan is created until saved. Numeracy and results without an individual reading level require the teacher to choose an intervention. Plans do not change scores or assign practice missions automatically; the existing Assign practice action remains available.

Plans belong to one assessment submission, so separate attempts retain separate plans. Only the assessment's creating teacher can manage them. Notes stay in teacher views and are not shown in student activities. The `intervention_plans` table is created by `2026_09_25_000001_create_intervention_plans_table`.

Tests: `php artisan test --filter=InterventionPlanTest --do-not-cache-result`.

## Intervention Follow-ups

- A teacher can link a published assessment in the student's section to an action plan. The follow-up must have the same subject and reading assessment type; numeracy worksheets must use the same worksheet number. A different assessment requires the teacher to record why its skills and difficulty are comparable. Matching a type alone does not establish equivalent difficulty.
- The link records its start time. The first new scored submission from that student for the linked assessment becomes the follow-up. Earlier attempts and other students' results are excluded. Later retries and note edits do not replace it. Changing or removing the linked assessment resets that plan's follow-up link. Linking does not grant retries or unlock an assessment; existing token permissions still apply.
- The profile and individual report compare the original and follow-up percentages, reading levels, and oral-reading miscues with the word count for each passage. Changes are percentage points, not additional points or coins. Missing observations remain unrecorded, and group screening is not presented as an individual reading level. Worksheet results appear after their existing teacher-scoring step.
- The Reports page lists teacher-owned plans, with due dates and Due today / Overdue / Awaiting result / Follow-up completed indicators. Follow-up completion does not automatically close the teacher's action plan. These comparisons describe score changes and do not prove that an intervention caused them.
- Apply `2026_09_25_000002_add_intervention_follow_ups` when deploying. Follow-up links and the selected result are stored in `intervention_plans`; both student and teacher-assisted submissions use the shared submission hook.

Tests: `php artisan test --filter=Intervention --do-not-cache-result`.
