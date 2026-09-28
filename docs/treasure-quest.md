# Treasure Quest

Select **Treasure Quest** in the teacher's literacy assessment builder. It uses
the assessment's existing questions, answer keys, and configured retry policy.
It does not select adaptive difficulty; question assignment remains unchanged.

- Silent reading keeps the story and start/end reading timer before the game.
- Listening comprehension uses the same game without displaying the passage.
- Oral reading remains the reading/marking activity, without a game.
- Each answer opens a chest. Correct answers reveal a gem; incorrect answers
  reveal sand. Both advance the trail. Continue leaves time to read feedback.
- Gems represent correct answers, not an additional currency. The existing
  server-side scoring and coin rewards determine the recorded result.
- Progress uses the existing per-student attempt key, revisioned server save,
  and local backup. Reopening continues at the first unanswered question.
- Teacher-assisted play uses the selected student's avatar, answers, and score.
- Three.js renders the island and chests. If WebGL fails, the illustrated
  fallback and answer buttons remain usable. Reduced motion and sound settings,
  text-size controls, dark mode, and end-of-game music fade remain available.

## Verification

`php artisan test --do-not-cache-result --filter=TreasureQuestTest`

For browser fixture capture, set `CAPTURE_TREASURE_FIXTURES=1` while running that
test after `npm run build`. Fixtures are written only to ignored `storage/app`.
The test uses the configured testing database, not production student records.
