# Literacy Scoring

Reference: [DepEd Phil-IRI Manual, 2018](https://lrmds.deped.gov.ph/detail/13908), Tables 7-8 and the Grade 6 group screening procedure.

- Teacher-authored activities are labelled **Phil-IRI-based**, not certified Phil-IRI tests or report-card grades.
- Comprehension: Independent >=80%, Instructional >=59%, otherwise Frustration. Oral word reading: Independent >=97%, Instructional >=90%, otherwise Frustration. Classifications use unrounded ratios; display uses two decimals.
- Oral profiles require both measures and use the lower level. Only the creating teacher confirms miscues, passage word count, optional time, and orally administered comprehension when there were no online questions.
- Oral marking is red-only: click a word to mark or clear it, or toggle a whole sentence. Each red word occurrence counts once; punctuation-only tokens are excluded. Complete saved marks produce a provisional word-reading percentage, `(words - red marks) / words * 100`, and prefill the teacher's miscue count. Only teacher confirmation establishes a verified result. Missing/incomplete marks do not imply 100% accuracy. Old yellow marks are cleared when resuming, not converted to errors; previously saved scores are unchanged.
- Silent/listening results use comprehension only. WPM is descriptive, not a grading threshold. Zero does not automatically identify a non-reader.
- Group screening uses the 14/20 cutoff only for 20-item activities, never an inferred individual reading level.
- `assessment_submissions.phil_iri` stores versioned results and teacher review identity/time. Older records derive comprehension from saved counts; missing oral observations remain pending.
- Comprehension interpretations explain the saved comprehension level, not the overall oral reading level, with a short practice suggestion. They appear on completion and in student/teacher records, including older results without interpretation text. Group screening uses its own cutoff explanation; an unrecorded score is not interpreted as zero. The guidance is plain-language application copy, not a diagnostic conclusion or a quotation from the manual.
- EXP, retries, leaderboard points, and numeracy are unchanged. Run `php artisan migrate` when deploying.

Tests: `php artisan test --filter=PhilIri --do-not-cache-result`.
