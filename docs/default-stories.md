# Default Stories

The teacher assessment builder's **Default story** selector contains five user-provided passages and 40 questions from `resources/data/literacy-stories.json`. These are classroom templates, not standardized Phil-IRI passages.

**Use Story** copies the selected title, passage, choices, and answer keys into the existing form. Teachers can edit the copy; the built-in original stays unchanged. Existing story/question content requires confirmation before replacement. Assessment title is filled only when blank or still equal to the previously auto-filled template title.

Teachers retain the selected reading mode, game, section, retry settings, and instructions. Oral reading disables the online questions and saves the passage only. Switching back to a question-based mode restores the question cards. Eight-item templates are not complete 20-item Grade 6 group screening tests.

Templates are loaded only by the authorized teacher builder, not bundled into public JavaScript. Viewing/loading a template creates no database records or notifications. The existing Save/Publish actions persist a teacher-owned assessment copy; students receive only published assessments assigned to them.

Test: `php artisan test --filter=LiteracyStoryLibraryTest --do-not-cache-result`.
