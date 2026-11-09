<?php

namespace Tests\Unit;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Support\StudentProgress;
use Carbon\Carbon;
use Tests\TestCase;

class StudentProgressTest extends TestCase
{
    public function test_numeracy_progress_compares_scores_and_operational_levels(): void
    {
        $first = $this->submission(1, 'First Math Check', 2, Carbon::parse('2026-11-08 10:00:00'));
        $latest = $this->submission(2, 'Latest Math Check', 3, Carbon::parse('2026-11-09 10:00:00'));

        $progress = StudentProgress::numeracy([$latest, $first]);

        $this->assertSame(75, $progress['latest_score']);
        $this->assertSame(50, $progress['previous_score']);
        $this->assertSame(25, $progress['change']);
        $this->assertSame('Improved', $progress['status_label']);
        $this->assertSame('Beginning to Proficient', $progress['level_transition']);
    }

    private function submission(int $id, string $title, int $correct, mixed $submittedAt): AssessmentSubmission
    {
        $assessment = new Assessment(['title' => $title, 'subject' => 'numeracy']);
        $submission = new AssessmentSubmission([
            'correct_count' => $correct,
            'question_count' => 4,
            'submitted_at' => $submittedAt,
        ]);
        $submission->id = $id;
        $submission->setRelation('assessment', $assessment);

        return $submission;
    }
}
