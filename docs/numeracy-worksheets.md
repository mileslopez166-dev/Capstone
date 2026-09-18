# Numeracy Worksheet Books

The supplied ARAL Grade 6 PDF is available as 35 separately assignable worksheets
(71 exercise pages). The apparent Worksheet 36 is a back cover with a hidden heading,
not an exercise, and is excluded. Original page images, diagrams and source attribution
are retained under `resources/worksheets/aral-g6`, served through authenticated routes.

Selecting **Numeracy** in the assessment builder opens the worksheet mission library
directly, without game quiz-type choices. Old numeracy-game creation requests redirect
there too; previously created game assessments remain intact.

Teachers open **Assessments > Numeracy**, choose a worksheet, set its scored
item count, section and retry allowance, then save locked or publish. Existing game
assessments are unchanged. Each worksheet groups all of its original parts together.

All 71 parts have selectable HTML text, with enlarged, reflowable instructions and
problems transcribed in `resources/data/numeracy-text.json`. Polygons, clocks, prisms and
fraction grids use scalable diagrams; original thermometer figures remain where needed
to read the instrument accurately. The original full-page scan and drawing surface are
available in the collapsed **Original page & drawing** section, preserving saved ink.

Students can resize text, navigate parts, type answers and draw on each page. Text, normalized
ink strokes and current part autosave to the database, with a device backup for interrupted
connections. Submission freezes the responses. Teachers review their own assignments via
**Reports > Worksheet Reviews**, award the number of correct items, and provide corrections.
Students can revisit both their submitted work and the teacher's feedback in Activities.

Each part starts with a **Read** step and a **Ready to Answer** action. The **Answer**
workspace places fixed answer controls directly beside each problem. Formats are defined
by worksheet, part and item in `WorksheetResponses`, not guessed from instruction text:

- Divisibility uses multi-select checkboxes, missing-digit boxes, or Yes/No plus justification.
- Operations provide a solution space and final answer; GCF/LCM tasks have separate fields.
- Fraction tasks have whole-number, numerator and denominator fields alongside any shading model.
- Polygon tasks ask for the missing name, side count, or a dedicated drawing; classification uses Regular/Irregular choices.
- Time conversion has hour/minute fields and AM/PM only for 12-hour answers.
- Area and volume tasks include unit choices and individual dimensions when requested.
- Temperature tasks use numeric readings or the existing interactive thermometer.

Students cannot add arbitrary questions or renumber these controls. Previous free-form rows
remain under **Earlier saved answers**, and page-level working notes and original-page ink
are preserved. Next Part opens the next reading page. Saved progress includes the active
Read/Answer step. Both students and teachers review the same completed controls, disabled
after submission. New item responses are saved in `pages.*.responses` keyed by section and
item, with server validation against the worksheet's fixed field definitions. Invalid
options, unrelated fields, impossible time ranges and out-of-bounds drawings are rejected.
Per-item drawings allow 100 strokes, 1,000 normalized points per stroke. Empty controls
do not count as a response; zero and No do. The progress meter counts parts with work,
not correctness or a claim that every item was answered. Teachers still grade omissions.

Reading settings include worksheet text (16-32px), answer text (16-28px), line spacing
(1.4-2.0), and optional original-page zoom (100-300%). Worksheet and answer text share
the existing story and answer size preferences; zoom and spacing use
`pgaals-worksheet-view` device preferences. Reset restores worksheet/story text to 22px,
answers to 18px, spacing to 1.7, and original zoom to 100%, without changing sound,
motion, or question text size. Settings are available during review too.
Legacy rows are limited to 100 per part with a 40-character item label and 2,000-character answer.
Empty rows or item labels alone do not count as answered parts. These are teacher-reviewed
responses, not generated questions or automatic marking.

Fraction grids can be shaded in Answer mode. Shaded cells autosave and remain visible
in teacher review. Grid identifiers and cell bounds are validated against the assigned
worksheet. Temperature marking controls save an item-specific temperature response.
Reading-only parts (Worksheet 3, Part 1) do not need fabricated answers to continue.

The multiplication-table button opens a teacher-approval dialog. The teacher who created
the assignment enters their own account password privately on the student's device.
The server checks the password hash, active teacher account, student ownership, section,
assignment status, and unsubmitted attempt. Password checks are rate limited to five per
minute. The password is not saved in worksheet state or browser storage. Approval is a
separate timestamp on `assessment_progress`, not a client-editable flag. The 1-12 table
is only returned after approval, with no-store response headers. Approval survives
resuming that attempt; every retake starts locked again. Use HTTPS outside local development.

Students open **Your Numeracy Mission** from their dashboard or **Activities > Numeracy** to see all 35
worksheet steps. Unassigned worksheets stay locked. The next action resumes saved work
first, then the lowest-numbered available worksheet. Mission completion counts distinct
worksheet numbers submitted, not retakes or points. Students can continue while waiting
for review, and completing all 35 submissions shows Mission Complete. Scores still require
teacher review. Teachers retain control of which worksheets are published to each section.

There is no answer key in the supplied PDF. This implementation does not pretend to
automatically grade drawings or free-text responses. Pending work earns no points and
does not appear as a zero score. Teacher review creates one normal assessment submission,
using the existing 250-points-per-correct-item scoring. Retry tokens are consumed once
when a retake is submitted, not again during review. Existing best-score leaderboard
aggregation remains unchanged.

Deployment: run `php artisan migrate` and `npm run build`. Commit the catalog and rendered
pages along with the code. No Python/PDF dependency is needed on the application server.
To regenerate the page assets, install PyMuPDF as a development tool and run
`python scripts/import-aral-worksheets.py "path/to/source.pdf"`.
Extract the original thermometer figures with
`python scripts/extract-worksheet-figures.py "path/to/source.pdf"`.
Text transcriptions are maintained separately and are not replaced by PDF image import.
