# Teacher-assisted assessments

For supervised use when students share a teacher's device:

1. Open a teacher-created assessment (literacy or a numeracy worksheet).
2. Under **Take with a Student**, select an approved student in the assigned section and choose **Start / Resume**.
3. Confirm the student's name in the teacher-assisted banner before entering answers.
4. Submit normally. Worksheet answers still require teacher review and grading.

The student's teacher-facing profile also lists eligible assessments with **Start / Resume** links.

The teacher stays signed in; this is not a student login or an unattended kiosk. Teacher navigation remains available, so supervise students using the device. No student password is needed.

## Data and access

- Only the assessment's creator can administer it. Each answering, saving, submitting, and multiplication-table request checks ownership, teacher approval, student approval, section, and published status.
- Answers, progress, scores, notifications, and retake usage belong to the selected student. Request payloads cannot override the participant; teacher routes explicitly bind both assessment and student.
- In-progress work resumes on either the teacher's device or the student's account. Local backups include the student and attempt key. Opening another student's attempt does not reuse the previous student's work.
- Existing retry limits apply. If no tries remain, the teacher returns to the student's profile with a pending retake request to approve. A pending worksheet opens its review instead of creating another attempt.
- `assessment_progress.administered_by` records the teacher when assisted work is saved or submitted. It is server-controlled, not mass assignable. Submissions link to the progress record, including worksheet attempts. Merely opening an assessment does not mark it as assisted.
- The multiplication table still needs the assessment teacher's password, with the existing rate limit and attempt-specific grant.

Run `php artisan migrate` when deploying to add the nullable assistance audit field; existing attempts remain unchanged.

Coverage: `tests/Feature/TeacherAssistedAssessmentTest.php` checks participant isolation, authorization on all endpoints, resume, scoring, oral marks, retries, idempotent submissions, and worksheet review/grading.
