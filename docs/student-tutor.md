# Student AI Tutor

Students open **Activities > Ask Tutor**, choose Literacy or Numeracy, and ask a study question. Completed assessments also have **Ask Tutor about this assessment**. The review remains optional. This feature does not change grading, retakes, points, or the planned machine-learning work.

## Activate Safely

1. Revoke any API key shared in chat or a screenshot. Create a replacement in your OpenAI project. Do not send the replacement through chat or commit it.
2. Before using real children's data, review your school's consent, privacy and retention obligations. Grade 6 learners may be under 13 or the applicable age of digital consent. OpenAI requires Zero Data Retention (ZDR) for processing these children's personal data. Obtain approval and configure it on the OpenAI project; a local setting cannot enable or verify ZDR.
3. Add these settings to the server's ignored `.env`, entering the replacement key locally:

   ```dotenv
   OPENAI_TUTOR_ENABLED=false
   OPENAI_API_KEY=
   OPENAI_TUTOR_MODEL=gpt-6-astra
   ```

4. Configure OpenAI billing and project usage controls. Confirm the project has access to the chosen model and the Responses and Moderation APIs. The configured model must support the Responses API and low reasoning effort. Model availability cannot be verified without a valid key.
5. After the school's privacy setup and account configuration are ready, set `OPENAI_TUTOR_ENABLED=true`, run `php artisan config:clear` (or rebuild the production config cache), and restart long-running PHP workers. Never use a `VITE_` variable for the key.
6. On another deployment, run `php artisan migrate` and `npm run build`. Preserve the application's `APP_KEY`; stored chat messages use Laravel encryption with that key.
7. First test with an adult test account and nonpersonal sample questions. Verify helpful replies, safety refusals, teacher escalation, and deletion before allowing children to use it.

The integration is disabled by default, even if a key is present. The student screen explains when it is not connected. No exposed key was used during implementation; automated tests simulate OpenAI responses.

## Data and Safeguards

- The browser calls authenticated Laravel endpoints, never OpenAI directly. Approved student accounts only; each conversation and completed submission is owner-checked on the server.
- The server sends the question, up to four previous exchanges, and a limited completed-assessment passage/question context. Worksheet images/PDFs are not uploaded; students can type the specific problem when needed. Account names, emails, photos, grades, teacher notes, and answer keys are excluded from automatic context. User-entered text and teacher-written passages may still contain personal information.
- Input and output pass through `omni-moderation-latest`. If moderation is unavailable or flags content, no model answer is shown or saved. Safety checks and tutor instructions reduce risks but are not a guarantee; teacher supervision and testing are still needed.
- `store: false` prevents Responses API application-state storage. It **does not** turn on ZDR or eliminate default abuse-monitoring retention. OpenAI's data controls apply to transmitted content independently of local chat deletion.
- Questions and replies are encrypted in `tutor_turns`. Students can reopen or permanently delete their own local conversations. Deletion cascades to local turns, but does not independently delete database backups or provider-held logs. Apply the school's backup and retention policy separately.
- AI text is rendered as text, never executable HTML. No browsing, file access, tools, scoring, or account-changing actions are granted to the model.
- A teacher-help button notifies the assessment creator, or the student's section teacher(s) for general chats. It shares only that learning help is requested, not the chat transcript. One notification per chat per day.
- Default limits: 5 attempts/minute and 30 attempts/rolling 24 hours/student, 1,500 characters/question, 40 exchanges/chat, 2,048 generated tokens/request. Limits count attempted calls, including filtered/failed requests. They are application safeguards, not a guaranteed billing cap.
- A per-student cache lock serializes calls. Successful retries with the same request UUID return the saved response without another paid generation. There are no automatic paid retries. A provider call that succeeded but could not be saved may still be billed before a manual retry.
- Use a persistent shared cache with atomic locks (for example Redis) across production instances; do not use the `array` test cache in production.
- Allow at least 75 seconds for PHP and proxy request timeouts on this endpoint. The bounded generation and two moderation checks can take up to 65 seconds; the browser waits 80 seconds and the application lock expires after 90 seconds.
- Connection failures and upstream errors return generic messages without API keys or request bodies. Avoid logging tutor request/response bodies in reverse proxies, monitoring, or debugging tools.

## Verification

```shell
php artisan test --filter=StudentTutorTest --do-not-cache-result
node --test tests/js/student-tutor.test.mjs
npm run build
```

Tests use mocked HTTP, not live billing. Real answer quality, model access, project ZDR approval, and provider limits require validation in the configured OpenAI account.

Official references: [Responses text generation](https://developers.openai.com/api/docs/guides/text), [Moderation](https://developers.openai.com/api/docs/guides/moderation), [Under-18 guidance](https://developers.openai.com/api/docs/guides/safety-checks/under-18-api-guidance), [Data controls](https://developers.openai.com/api/docs/guides/your-data), [Configured model](https://developers.openai.com/api/docs/models/gpt-6-astra).
