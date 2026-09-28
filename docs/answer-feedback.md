# Answer review help

Students can open Activities > Recorded Outputs > Assessment Review and choose
"Help me understand" beside a missed multiple-choice answer. This is opt-in,
post-submission help, not a scoring step or a live-game answer hint.

The existing OpenAI tutor supplies three short sections: an explanation, a
reading/math strategy, and a similar unscored practice question. Only the saved
attempt's reviewed item, selected option, correct option, subject, and passage
are sent. No profile, ability label, other questions, or browser-supplied key is
used. AI may still make mistakes; the teacher's key and server scores remain
authoritative. Live assessment help continues to exclude answer keys.

Feedback is encrypted in `assessment_answer_feedback`, unique to submission and
question. Reopening retrieves it without another API request. A source hash
invalidates stale help if legacy question material changes. Saved explanations
remain available if OpenAI is disabled. The tutor's existing per-user lock,
minute/day limits and input/output moderation apply; failures are not saved.
No generated feedback is automatically sent on page load.

Requires existing server-side `OPENAI_TUTOR_ENABLED` and `OPENAI_API_KEY` settings.
Never expose the key in browser code. Run `php artisan migrate` and
`npm run build` on deployment. This adds one table; no assessment data is reset.

Scope: the multiple-choice review shared by the literacy games and manual
questions. Worksheet-specific answer formats and oral pronunciation marks are
not included in this first integration.

Checks: `php artisan test --filter=AnswerFeedbackTest` and
`node --test tests/js/answer-feedback.test.mjs`. API responses are mocked in
tests; no student data or paid requests are sent.
