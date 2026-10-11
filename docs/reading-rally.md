# Reading Rally

Choose Reading Rally in the literacy assessment builder. Students read or listen
to the assigned passage, then race using its multiple-choice questions.

- A correct answer boosts the red car; a wrong answer causes a brief skid and a
  passage hint. Each question accepts one answer. Next question becomes available
  after the reaction. A timeout records TIMEOUT and automatically advances.
- Race position comes from correct and incorrect answers, not reading speed.
  Three scripted rivals represent pace thresholds. The race margin is
  `12 * (correct - 0.5 * incorrect) / question_count`; rivals have margins 1, 4,
  and 8. A tie leaves the rival ahead. All-correct finishes first.
- Saved answers restore race progress and position. Server scoring still controls
  assessment percentages, EXP, coins, adaptive questions, and ML inputs.
- Three.js renders the cars and track. A 2D track is available without WebGL.
  Portrait and landscape layouts, dark mode, reduced motion, and avatar outfits
  are supported. Animation pauses offscreen and stops at the result screen.
- Existing `word_rescue` and `treasure_quest` records open Reading Rally. Pending
  legacy retry choices retain their first answer. New records use `reading_rally`.
  No database migration is required.
