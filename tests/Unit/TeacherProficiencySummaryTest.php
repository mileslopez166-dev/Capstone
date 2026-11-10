<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Support\PhilIri;
use App\Support\TeacherProficiencySummary;
use Tests\TestCase;

class TeacherProficiencySummaryTest extends TestCase
{
    public function test_subjects_use_their_own_levels_and_count_each_student_once(): void
    {
        $oral = new Assessment(['subject' => 'literacy', 'assessment_type' => 'oral_reading',
            'story_description' => implode(' ', array_fill(0, 100, 'word'))]);
        $older = $this->submission(1, 1, $oral, 0, 0);
        $older->phil_iri = PhilIri::initial($oral, 0, 0, ['word_marks' => array_fill(0, 100, 0)]);
        $latest = $this->submission(2, 1, $oral, 0, 0);
        $latest->phil_iri = PhilIri::initial($oral, 0, 0, ['word_marks' => array_merge(array_fill(0, 19, 2), array_fill(0, 81, 0))]);
        $math = new Assessment(['subject' => 'numeracy', 'assessment_type' => 'worksheet']);
        $numeracy = $this->submission(3, 1, $math, 19, 20);
        $beginner = $this->submission(4, 2, $math, 4, 10);
        $missing = $this->submission(5, 3, $oral, 0, 0);
        $draft = $this->submission(6, 4, $math, 10, 10);
        $draft->submitted_at = null;

        $counts = TeacherProficiencySummary::fromSubmissions([$older, $numeracy, $latest, $beginner, $missing, $draft]);

        $this->assertSame(['Frustration' => 1, 'Instructional' => 0, 'Independent' => 0], $counts['literacy']);
        $this->assertSame(['Beginning' => 1, 'Developing' => 0, 'Proficient' => 0, 'Advanced' => 1], $counts['numeracy']);
        $this->assertSame(['literacy' => array_fill_keys(['Frustration', 'Instructional', 'Independent'], 0),
            'numeracy' => array_fill_keys(['Beginning', 'Developing', 'Proficient', 'Advanced'], 0)],
            TeacherProficiencySummary::fromSubmissions([]));
    }

    private function submission(int $id, int $student, Assessment $assessment, int $correct, int $total): AssessmentSubmission
    {
        $submission = new AssessmentSubmission(['user_id' => $student, 'correct_count' => $correct,
            'question_count' => $total, 'submitted_at' => now()->subMinutes(10 - $id)]);
        $submission->id = $id;
        $submission->setRelation('assessment', $assessment);

        return $submission;
    }
}
