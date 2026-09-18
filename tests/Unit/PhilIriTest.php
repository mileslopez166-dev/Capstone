<?php

namespace Tests\Unit;

use App\Support\PhilIri;
use PHPUnit\Framework\TestCase;

class PhilIriTest extends TestCase
{
    public function test_comprehension_boundaries_do_not_promote_rounded_scores(): void
    {
        foreach ([0 => 'Frustration', 58 => 'Frustration', 59 => 'Instructional', 79 => 'Instructional', 80 => 'Independent', 100 => 'Independent'] as $score => $expected) {
            $this->assertSame($expected, PhilIri::comprehensionLevel($score));
        }
        $this->assertSame('Frustration', PhilIri::comprehensionLevel(58.99));
        $this->assertSame('Instructional', PhilIri::comprehensionLevel(79.99));
    }

    public function test_word_reading_boundaries_do_not_promote_rounded_scores(): void
    {
        foreach ([0 => 'Frustration', 89 => 'Frustration', 90 => 'Instructional', 96 => 'Instructional', 97 => 'Independent', 100 => 'Independent'] as $score => $expected) {
            $this->assertSame($expected, PhilIri::wordReadingLevel($score));
        }
        $this->assertSame('Frustration', PhilIri::wordReadingLevel(89.99));
        $this->assertSame('Instructional', PhilIri::wordReadingLevel(96.99));
    }

    public function test_oral_profile_requires_both_measures_and_uses_lower_level(): void
    {
        foreach ([3 => 'Independent', 10 => 'Instructional', 11 => 'Frustration'] as $miscues => $wordLevel) {
            foreach ([8 => 'Independent', 6 => 'Instructional', 5 => 'Frustration'] as $correct => $comprehensionLevel) {
                $levels = ['Independent', 'Instructional', 'Frustration'];
                $expected = $levels[max(array_search($wordLevel, $levels), array_search($comprehensionLevel, $levels))];
                $result = PhilIri::calculate([
                    'assessment_type' => 'oral_reading', 'word_count' => 100, 'miscues' => $miscues,
                    'correct_count' => $correct, 'question_count' => 10, 'reading_seconds' => 120,
                    'reviewed_at' => '2026-09-17T00:00:00Z',
                ]);
                $this->assertSame($expected, $result['level']);
                $this->assertSame(PhilIri::comprehensionInterpretation([
                    'assessment_type' => 'oral_reading', 'question_count' => 10,
                    'comprehension_level' => $comprehensionLevel,
                ]), $result['comprehension_interpretation']);
                $this->assertEquals(50, $result['words_per_minute']);
            }
        }
    }

    public function test_word_count_ignores_whitespace_and_punctuation_only_tokens(): void
    {
        $this->assertSame(0, PhilIri::wordCount(null));
        $this->assertSame(0, PhilIri::wordCount(" ... -- ! \n"));
        $this->assertSame(5, PhilIri::wordCount("One two.\n\nThree-four don't 6 ..."));
    }

    public function test_comprehension_interpretation_matches_unrounded_grade_boundaries(): void
    {
        foreach (['silent_reading', 'listening_comprehension'] as $type) {
            foreach ([0 => 'currently challenging', 5899 => 'currently challenging', 5900 => 'developing understanding', 7999 => 'developing understanding', 8000 => 'strong understanding', 10000 => 'strong understanding'] as $correct => $expected) {
                $result = PhilIri::calculate([
                    'assessment_type' => $type, 'word_count' => 100, 'miscues' => null,
                    'correct_count' => $correct, 'question_count' => 10000, 'reading_seconds' => null,
                    'reviewed_at' => null,
                ]);
                $this->assertStringContainsString($expected, $result['comprehension_interpretation']);
            }
        }
    }

    public function test_red_marks_count_occurrences_not_unique_words_and_ignore_punctuation(): void
    {
        $passage = "Read read.\n\nRead again! -- ...";
        $summary = PhilIri::oralReadingMarks($passage, ['word_marks' => [2, 2, 2, 0, 2, 2]]);
        $this->assertSame(['word_count' => 4, 'marked_miscues' => 3], $summary);
        $this->assertSame(['word_count' => 4, 'marked_miscues' => 0], PhilIri::oralReadingMarks($passage, ['word_marks' => [0, 0, 0, 0, 0, 0]]));
        $this->assertSame(['word_count' => 2, 'marked_miscues' => 1], PhilIri::oralReadingMarks('Read.Again!', ['word_marks' => [0, 2]]));
    }

    public function test_incomplete_or_legacy_marks_cannot_imply_perfect_reading(): void
    {
        foreach ([[], ['word_marks' => []], ['word_marks' => [2]], ['word_marks' => [0, 0, 2]], ['word_marks' => [1, 2]], ['word_marks' => [1 => 2, 2 => 0]]] as $state) {
            $this->assertSame(['word_count' => 2, 'marked_miscues' => null], PhilIri::oralReadingMarks('Two words.', $state));
        }
        $this->assertNull(PhilIri::oralReadingMarks('... !', ['word_marks' => [2, 2]])['marked_miscues']);
    }

    public function test_missing_comprehension_is_explained_without_assigning_a_low_grade(): void
    {
        foreach (['oral_reading', 'silent_reading', 'listening_comprehension'] as $type) {
            $result = PhilIri::calculate([
                'assessment_type' => $type, 'word_count' => 100, 'miscues' => null,
                'correct_count' => 0, 'question_count' => 0, 'reading_seconds' => null,
                'reviewed_at' => null,
            ]);
            $this->assertStringContainsString('has not been assessed yet', $result['comprehension_interpretation']);
            $this->assertNull($result['comprehension_level']);
        }
    }

    public function test_group_screening_has_its_own_interpretation(): void
    {
        foreach ([[13, 20, 'Further individual assessment'], [14, 20, 'meets the Grade 6 screening cutoff'], [5, 5, 'A 20-item test is needed']] as [$correct, $questions, $expected]) {
            $result = PhilIri::calculate([
                'assessment_type' => 'group_screening', 'word_count' => 100, 'miscues' => null,
                'correct_count' => $correct, 'question_count' => $questions, 'reading_seconds' => null,
                'reviewed_at' => null,
            ]);
            $this->assertStringContainsString($expected, $result['comprehension_interpretation']);
            $this->assertNull($result['comprehension_level']);
        }
    }
}
