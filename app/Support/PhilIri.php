<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;

class PhilIri
{
    public const VERSION = 'phil-iri-2018-v1';

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
        if ($result['assessment_type'] === 'group_screening') {
            if ((int) $result['question_count'] !== 20) {
                return 'A 20-item test is needed to interpret this Grade 6 screening result. This score does not establish an individual reading level.';
            }

            return $result['correct_count'] >= 14
                ? 'This score meets the Grade 6 screening cutoff. It does not establish an individual reading level; continue regular comprehension practice.'
                : 'This score is below the Grade 6 screening cutoff. Further individual assessment with the teacher is needed to identify the right reading support.';
        }

        if ((int) $result['question_count'] === 0) {
            return 'Comprehension has not been assessed yet. An interpretation will be available once the comprehension score is recorded.';
        }

        return match ($result['comprehension_level'] ?? null) {
            'Independent' => 'The score indicates strong understanding of this passage at the independent level. Continue practicing and explaining answers using details from the passage.',
            'Instructional' => 'The score indicates developing understanding of this passage. Guided practice and discussion with the teacher can help strengthen comprehension.',
            'Frustration' => 'The score indicates that this passage is currently challenging to understand. Work with the teacher on shorter or easier passages and discuss their meaning step by step.',
            default => 'A comprehension interpretation is not available for this result.',
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
            if (trim($paragraph) === '') continue;
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
        if (!$words || !is_array($marks) || array_keys($marks) !== array_keys($tokens)) return $result;
        // Missing marks and old yellow notes must not become a perfect reading score.
        foreach ($marks as $mark) {
            if (!in_array($mark, [0, 2, '0', '2'], true)) return $result;
        }
        $result['marked_miscues'] = count(array_filter(array_keys($words), fn ($index) => (int) $marks[$index] === 2));

        return $result;
    }

    public static function forSubmission(AssessmentSubmission $submission): ?array
    {
        if ($submission->phil_iri !== null) {
            $result = $submission->phil_iri;
            $result['comprehension_interpretation'] = self::comprehensionInterpretation($result);

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
        $comprehension = $questions > 0 ? $result['correct_count'] * 100 / $questions : null;
        $result['comprehension_percent'] = $comprehension !== null ? round($comprehension, 2) : null;
        $result['comprehension_level'] = $comprehension !== null ? self::comprehensionLevel($comprehension) : null;
        $result['word_reading_percent'] = null;
        $result['word_reading_level'] = null;
        $result['word_reading_provisional'] = false;
        $result['words_per_minute'] = $words > 0 && $seconds > 0 ? round($words * 60 / $seconds, 1) : null;
        $result['level'] = null;
        $result['status'] = 'incomplete';
        $result['label'] = 'Comprehension not recorded';
        $result['measure'] = $type === 'listening_comprehension' ? 'Listening comprehension' : 'Reading comprehension';

        if ($type === 'group_screening') {
            $result['measure'] = 'Group screening';
            $result['comprehension_level'] = null;
            $result['status'] = $questions === 20 ? 'screening' : 'unsupported_screening';
            $result['label'] = $questions !== 20 ? '20-item GST required'
                : ($result['correct_count'] >= 14 ? 'At or above screening cutoff' : 'Further assessment needed');
            $result['comprehension_interpretation'] = self::comprehensionInterpretation($result);
            return $result;
        }

        if ($type === 'oral_reading') {
            $result['measure'] = 'Oral reading profile';
            $result['status'] = 'awaiting_teacher';
            $result['label'] = 'Awaiting teacher scoring';
            if (!$result['reviewed_at'] && $words > 0 && isset($result['marked_miscues'])) {
                $wordReading = max(0, $words - (int) $result['marked_miscues']) * 100 / $words;
                $result['word_reading_percent'] = round($wordReading, 2);
                $result['word_reading_level'] = self::wordReadingLevel($wordReading);
                $result['word_reading_provisional'] = true;
            }
            if ($result['reviewed_at'] && $words > 0 && $result['miscues'] !== null) {
                $wordReading = max(0, $words - (int) $result['miscues']) * 100 / $words;
                $result['word_reading_percent'] = round($wordReading, 2);
                $result['word_reading_level'] = self::wordReadingLevel($wordReading);
                $result['status'] = 'incomplete';
                $result['label'] = 'Comprehension not recorded';
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

        return $result;
    }
}
