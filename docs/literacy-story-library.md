# Literacy Story Library

The built-in teacher library now uses the five passages from
`resources/data/literacy-assessment-source.md`. The generated bank is
`resources/data/literacy-stories.json`.

- Each passage has eight questions per Frustration, Instructional, and Independent difficulty group.
- A Clearer Book Fair also has eight Advanced enrichment questions.
- Total: 128 questions, with original answer keys, skills, and explanations.
- Advanced is a question difficulty, not another Phil-IRI or ML classification.

Teachers choose a passage. The default library option is now Automatic: it
loads the full editable bank and serves eight questions per student. Manual
overrides load a fixed set of eight easier, moderate, challenging, or enrichment
questions. These are question-difficulty labels, not student reading diagnoses.
Oral reading uses the passage without comprehension questions. Custom questions
can still leave `difficulty` unspecified and use fixed delivery.

The template answer guide belongs to the original bank; it does not change
when a teacher edits a question. Existing database assessments and submissions
are not replaced. Student assessment screens do not display difficulty labels
or the teacher answer guide.

## Automatic Selection

Silent reading and listening comprehension support automatic delivery.
Group screening retains its fixed question set and existing GST rules;
numeracy worksheets are unchanged. Enrichment remains a manual option.

| Plan | Easier | Moderate | Challenging |
| --- | --- | --- | --- |
| Balanced starter | 3 | 3 | 2 |
| Support focus | 5 | 2 | 1 |
| Moderate focus | 2 | 4 | 2 |
| Challenge focus | 1 | 2 | 5 |

Rules-v1 uses the latest scored snapshot for each of the three most recent
distinct assessments from the same teacher, subject, and reading mode. Retakes
do not multiply their weight. Old results without snapshots are not used for
placement because their item-level difficulty cannot be reliably reconstructed.
Correct/total counts are pooled within each question band, not across unequal
difficulty sets. With fewer than two moderate items, use the balanced starter.
Otherwise, below 60% on moderate items selects support; at least 60% selects
moderate focus. At least 75% on moderate AND challenging items, with at least
two challenging items answered, selects challenge focus. The thresholds and
mixes are provisional pilot rules, not validated Phil-IRI placement or an ML
prediction. Evaluate them with teachers and real assessment data before using
them for consequential decisions. ML predictions remain separate.

Selection is deterministic for the saved attempt key, with no repeated bank
item in a set. A server-owned question snapshot and private selection context
are saved when opening an attempt. Later results or bank changes cannot replace
that set. Submission scoring, practice suggestions, reviews, and tutor context
use the snapshot. The final submission also stores its own copy. Automatic
submissions require the attempt key. Clients cannot replace the question set.

Teachers can open the full answer key on Created Assessments, and the exact
attempt key with student responses in Literacy Scoring. Teacher-assisted play
has a collapsed teacher-only key. Student pages omit selection context and
difficulty labels. Existing game feedback still receives the selected answer
keys client-side, as it did before; this is not a high-stakes anti-cheating exam.

Existing assessments remain fixed and existing results are not rewritten.
Create a new assessment with Automatic selected to use adaptive delivery.
Apply the additive `2026_09_29_000001_add_adaptive_question_snapshots` migration
on other devices/deployments before using the new code.

Regenerate or verify the bank using the existing CommonMark parser:

```powershell
php scripts/import-literacy-stories.php
php scripts/import-literacy-stories.php --check
```

The importer validates story/set counts and each answer against its teacher
guide before replacing the JSON file. It never writes to the database.
