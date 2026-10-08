<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;

class PhilIri
{
    public const VERSION = 'phil-iri-2018-v1';

    public static function assessmentTypeLabel(?string $type): string
    {
        return match ($type) {
            'oral_reading' => 'Oral Reading Assessment',
            'silent_reading' => 'Silent Reading Assessment',
            'listening_comprehension' => 'Listening Comprehension Assessment',
            'group_screening' => 'Group Screening Test',
            default => 'Literacy Assessment',
        };
    }

    public static function measureName(?string $type): string
    {
        return match ($type) {
            'oral_reading' => 'Oral reading profile',
            'silent_reading' => 'Silent reading comprehension',
            'listening_comprehension' => 'Listening comprehension',
            'group_screening' => 'Group screening result',
            default => 'Reading comprehension',
        };
    }

    public static function comprehensionLevel(float $percent): string
    {
        return $percent >= 80 ? 'Independent' : ($percent >= 59 ? 'Instructional' : 'Frustration');
    }

    public static function wordReadingLevel(float $percent): string
    {
        return $percent >= 97 ? 'Independent' : ($percent >= 90 ? 'Instructional' : 'Frustration');
    }

    public static function comprehensionInterpretation(array $result): string
    {
        $type = $result['assessment_type'] ?? 'silent_reading';
        $questionCount = (int) ($result['question_count'] ?? 0);
        $correctCount = (int) ($result['correct_count'] ?? 0);

        if ($type === 'group_screening') {
            if ($questionCount !== 20) {
                return 'A 20-item test is needed to interpret this Grade 6 screening result. This score does not establish an individual reading level.';
            }

            return $correctCount >= 14
                ? 'This score meets the Grade 6 screening cutoff. It does not establish an individual reading level; continue regular comprehension practice.'
                : 'This score is below the Grade 6 screening cutoff. Further individual assessment with the teacher is needed to identify the right reading support.';
        }

        if ($questionCount === 0) {
            return 'Comprehension has not been assessed yet. An interpretation will be available once the comprehension score is recorded.';
        }

        return match ($result['comprehension_level'] ?? null) {
            'Independent' => 'The score indicates strong understanding of this passage at the independent level. Continue practicing and explaining answers using details from the passage.',
            'Instructional' => 'The score indicates developing understanding of this passage. Guided practice and discussion with the teacher can help strengthen comprehension.',
            'Frustration' => 'The score indicates that this passage is currently challenging to understand. Work with the teacher on shorter or easier passages and discuss their meaning step by step.',
            default => 'A comprehension interpretation is not available for this result.',
        };
    }

    public static function practiceRecommendation(array $result): ?array
    {
        return match ($result['level'] ?? null) {
            'Independent' => [
                'label' => 'Enrichment challenge',
                'title' => 'Move into a harder reading task',
                'summary' => 'The student can work with a more challenging passage, explain answers using text evidence, and try a vocabulary or summary activity.',
                'steps' => [
                    'Read a slightly longer or richer passage.',
                    'Answer inference and vocabulary questions.',
                    'Write or tell a short summary using details from the text.',
                ],
                'action_label' => 'Open enrichment practice',
            ],
            'Instructional' => [
                'label' => 'Guided practice',
                'title' => 'Practice again with teacher support',
                'summary' => 'The student is ready for supported practice. Rereading, clue questions, and teacher discussion can help strengthen comprehension and accuracy.',
                'steps' => [
                    'Reread the passage with teacher guidance.',
                    'Review missed questions or red-marked words.',
                    'Practice answering with clues from the story.',
                ],
                'action_label' => 'Open guided practice',
            ],
            'Frustration' => [
                'label' => 'Remediation support',
                'title' => 'Use an easier support activity first',
                'summary' => 'The passage is currently difficult for the student. Start with shorter text, key word practice, and step-by-step discussion before another assessment.',
                'steps' => [
                    'Practice key words from the passage.',
                    'Use a shorter or easier reading passage.',
                    'Discuss one sentence or idea at a time with the teacher.',
                ],
                'action_label' => 'Open support practice',
            ],
            default => null,
        };
    }

    public static function wordCount(?string $passage): int
    {
        $tokens = preg_split('/\s+/u', trim($passage ?? ''), -1, PREG_SPLIT_NO_EMPTY);

        return count(array_filter($tokens, fn ($token) => preg_match('/[\p{L}\p{N}]/u', $token)));
    }

    public static function initial(Assessment $assessment, int $correct, int $questions, array $state = []): ?array
    {
        if ($assessment->subject !== 'literacy') {
            return null;
        }

        $type = $assessment->assessment_type ?? 'silent_reading';
        $result = [
            'version' => self::VERSION, 'assessment_type' => $type,
            'correct_count' => $correct, 'question_count' => $questions,
            'word_count' => self::wordCount($assessment->story_description),
            'marked_miscues' => null,
            'miscues' => null, 'reading_seconds' => null,
            'word_reading_source' => null,
            'reviewed_by' => null, 'reviewed_at' => null,
        ];
        if ($type === 'oral_reading') {
            $result = array_merge($result, self::oralReadingMarks($assessment->story_description ?: $assessment->instructions, $state));
        }
        if ($type === 'silent_reading' && ($state['timer_status'] ?? null) === 'finished' && ($state['reading_seconds'] ?? 0) > 0) {
            $result['reading_seconds'] = (int) $state['reading_seconds'];
        }

        return self::calculate($result);
    }

    public static function oralReadingMarks(?string $passage, array $state): array
    {
        $tokens = [];
        // Keep indexes aligned with the reader's paragraph, sentence, and word buttons.
        foreach (preg_split('/\R{2,}/u', trim($passage ?? '')) as $paragraph) {
            if (trim($paragraph) === '') {
                continue;
            }
            preg_match_all('/[^.!?]+[.!?]+|[^.!?]+$/u', $paragraph, $matches);
            foreach ($matches[0] ?: [$paragraph] as $sentence) {
                foreach (preg_split('/\s+/u', trim($sentence), -1, PREG_SPLIT_NO_EMPTY) as $token) {
                    $tokens[] = $token;
                }
            }
        }
        $words = array_filter($tokens, fn ($token) => preg_match('/[\p{L}\p{N}]/u', $token));
        $result = ['word_count' => count($words), 'marked_miscues' => null];
        $marks = $state['word_marks'] ?? null;
        if (! $words || ! is_array($marks) || array_keys($marks) !== array_keys($tokens)) {
            return $result;
        }
        // Missing marks and old yellow notes must not become a perfect reading score.
        foreach ($marks as $mark) {
            if (! in_array($mark, [0, 2, '0', '2'], true)) {
                return $result;
            }
        }
        $result['marked_miscues'] = count(array_filter(array_keys($words), fn ($index) => (int) $marks[$index] === 2));

        return $result;
    }

    public static function forSubmission(AssessmentSubmission $submission): ?array
    {
        if ($submission->phil_iri !== null) {
            $result = $submission->phil_iri;
            $result = self::withDisplayFields($result);

            return $result;
        }

        // Older attempts can use saved answer counts, but never assume an unmarked oral passage was error-free.
        return $submission->assessment
            ? self::initial($submission->assessment, $submission->correct_count, $submission->question_count)
            : null;
    }

    public static function calculate(array $result): array
    {
        $questions = (int) $result['question_count'];
        $words = (int) $result['word_count'];
        $seconds = $result['reading_seconds'];
        $type = $result['assessment_type'];
        $result['assessment_type_label'] = self::assessmentTypeLabel($type);
        $comprehension = $questions > 0 ? $result['correct_count'] * 100 / $questions : null;
        $result['comprehension_percent'] = $comprehension !== null ? round($comprehension, 2) : null;
        $result['comprehension_level'] = $comprehension !== null ? self::comprehensionLevel($comprehension) : null;
        $result['word_reading_percent'] = null;
        $result['word_reading_level'] = null;
        $result['word_reading_provisional'] = false;
        $result['word_reading_source'] = $result['word_reading_source'] ?? null;
        $result['words_per_minute'] = $words > 0 && $seconds > 0 ? round($words * 60 / $seconds, 1) : null;
        $result['level'] = null;
        $result['status'] = 'incomplete';
        $result['label'] = 'Comprehension not recorded';
        $result['measure'] = self::measureName($type);
        $result['practice_recommendation'] = null;

        if ($type === 'group_screening') {
            $result['comprehension_level'] = null;
            $result['status'] = $questions === 20 ? 'screening' : 'unsupported_screening';
            $result['label'] = $questions !== 20 ? '20-item GST required'
                : ($result['correct_count'] >= 14 ? 'At or above screening cutoff' : 'Further assessment needed');
            $result['comprehension_interpretation'] = self::comprehensionInterpretation($result);

            return $result;
        }

        if ($type === 'oral_reading') {
            $result['measure'] = 'Oral reading profile';
            $result['status'] = 'incomplete';
            $result['label'] = 'Reading marks not recorded';
            if ($result['reviewed_at'] && $words > 0 && $result['miscues'] !== null) {
                $wordReading = max(0, $words - (int) $result['miscues']) * 100 / $words;
                $result['word_reading_percent'] = round($wordReading, 2);
                $result['word_reading_level'] = self::wordReadingLevel($wordReading);
                $result['word_reading_source'] = 'teacher_review';
            } elseif ($words > 0 && ($result['marked_miscues'] ?? null) !== null) {
                $wordReading = max(0, $words - (int) $result['marked_miscues']) * 100 / $words;
                $result['word_reading_percent'] = round($wordReading, 2);
                $result['word_reading_level'] = self::wordReadingLevel($wordReading);
                $result['word_reading_source'] = 'red_marks';
            }

            if ($result['word_reading_level'] !== null) {
                $result['level'] = $result['word_reading_level'];
                if ($comprehension !== null) {
                    $levels = ['Independent', 'Instructional', 'Frustration'];
                    $result['level'] = $levels[max(array_search($result['word_reading_level'], $levels), array_search($result['comprehension_level'], $levels))];
                }
            }
        } elseif (in_array($type, ['silent_reading', 'listening_comprehension'], true)) {
            $result['level'] = $result['comprehension_level'];
        }

        if ($result['level'] !== null) {
            $result['status'] = 'complete';
            $result['label'] = $result['level'];
        }

        $result['comprehension_interpretation'] = self::comprehensionInterpretation($result);
        $result['practice_recommendation'] = self::practiceRecommendation($result);

        return $result;
    }

    public static function formulaRows(array $result, ?AssessmentSubmission $submission = null): array
    {
        $type = $result['assessment_type'] ?? 'silent_reading';
        $rows = [];
        $resultQuestions = (int) ($result['question_count'] ?? 0);
        $resultCorrect = (int) ($result['correct_count'] ?? 0);
        $wordCount = (int) ($result['word_count'] ?? 0);
        $readingSeconds = (int) ($result['reading_seconds'] ?? 0);
        $formatPercent = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.').'%';
        $formatNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
        $comprehensionLabel = match ($type) {
            'listening_comprehension' => 'Listening comprehension',
            'group_screening' => 'Screening score',
            default => 'Reading comprehension',
        };

        if ($resultQuestions > 0 && ($result['comprehension_percent'] ?? null) !== null) {
            $rows[] = [
                'label' => $comprehensionLabel,
                'value' => $resultCorrect.' / '.$resultQuestions.' x 100 = '.$formatPercent($result['comprehension_percent']),
            ];
        }

        if ($type === 'oral_reading' && $wordCount > 0 && ($result['word_reading_percent'] ?? null) !== null) {
            if (! empty($result['reviewed_at']) && ($result['miscues'] ?? null) !== null) {
                $miscues = (int) $result['miscues'];
                $rows[] = [
                    'label' => 'Word reading',
                    'value' => '('.$wordCount.' words - '.$miscues.' miscues) / '.$wordCount.' x 100 = '.$formatPercent($result['word_reading_percent']),
                ];
            } elseif (($result['word_reading_source'] ?? null) === 'red_marks' && ($result['marked_miscues'] ?? null) !== null) {
                $marked = (int) $result['marked_miscues'];
                $rows[] = [
                    'label' => 'Word reading',
                    'value' => '('.$wordCount.' words - '.$marked.' red-marked words) / '.$wordCount.' x 100 = '.$formatPercent($result['word_reading_percent']),
                ];
            }
        }

        if ($wordCount > 0 && $readingSeconds > 0 && ($result['words_per_minute'] ?? null) !== null) {
            $rows[] = [
                'label' => 'Reading rate',
                'value' => $wordCount.' words / '.$readingSeconds.' seconds x 60 = '.$formatNumber($result['words_per_minute']).' WPM',
            ];
        }

        if (
            $type === 'oral_reading'
            && ($result['level'] ?? null) !== null
            && ($result['word_reading_level'] ?? null) !== null
            && ($result['comprehension_level'] ?? null) !== null
        ) {
            $rows[] = [
                'label' => 'Overall oral profile',
                'value' => 'Lower of '.$result['word_reading_level'].' word reading and '.$result['comprehension_level'].' comprehension = '.$result['level'],
            ];
        }

        if ($type === 'group_screening' && $resultQuestions === 20) {
            $rows[] = [
                'label' => 'GST cutoff',
                'value' => $resultCorrect.' / 20 compared with 14 / 20 cutoff = '.($result['label'] ?? 'Screening result'),
            ];
        }

        if ($submission) {
            $pointQuestions = (int) $submission->question_count;
            $pointCorrect = (int) $submission->correct_count;
            $pointsPerCorrectAnswer = (int) config('gamification.points_per_correct_answer');

            if ($pointQuestions > 0) {
                $rows[] = [
                    'label' => 'Assessment points',
                    'value' => $pointCorrect.' correct x '.$pointsPerCorrectAnswer.' = '.number_format((int) $submission->points).' points',
                ];
                $rows[] = [
                    'label' => 'Possible points',
                    'value' => $pointQuestions.' questions x '.$pointsPerCorrectAnswer.' = '.number_format((int) $submission->possible_points).' points',
                ];
            } elseif ($type === 'oral_reading' && ($result['word_reading_percent'] ?? null) !== null) {
                $rows[] = [
                    'label' => 'Assessment points',
                    'value' => '0 question points; oral reading is scored through Phil-IRI.',
                ];
            }
        }

        return $rows;
    }

    private static function withDisplayFields(array $result): array
    {
        $type = $result['assessment_type'] ?? 'silent_reading';

        return self::calculate(array_merge([
            'version' => self::VERSION,
            'assessment_type' => $type,
            'correct_count' => 0,
            'question_count' => 0,
            'word_count' => 0,
            'marked_miscues' => null,
            'miscues' => null,
            'reading_seconds' => null,
            'word_reading_source' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ], $result));
    }
}
