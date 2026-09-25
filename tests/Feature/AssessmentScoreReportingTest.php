<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\AssessmentScores;
use App\Support\PhilIri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentScoreReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_oral_reading_uses_phil_iri_word_reading_score_in_reports(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['name' => 'Mila Santos', 'section' => 'Section A']);
        $assessment = $this->oralAssessment($teacher);
        $submission = $this->oralSubmission($assessment, $student, [
            'reviewed_by' => $teacher->id,
            'reviewed_at' => now()->toIso8601String(),
            'word_count' => 100,
            'miscues' => 4,
            'correct_count' => 4,
            'question_count' => 5,
        ]);

        $this->assertSame(96, AssessmentScores::percentage($submission->fresh('assessment')));

        $this->actingAs($teacher)->get(route('reports.index'))->assertOk()
            ->assertViewHas('reportMetrics', fn (array $metrics): bool => $metrics['average_accuracy'] === 96)
            ->assertViewHas('subjectBreakdown', fn ($breakdown): bool => $breakdown->firstWhere('label', 'Literacy')['accuracy'] === 96);

        $this->get(route('reports.student', $student))->assertOk()
            ->assertViewHas('studentMetrics', fn (array $metrics): bool => $metrics['average_accuracy'] === 96)
            ->assertSeeText('96%');

        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()
            ->assertViewHas('studentMetrics', fn (array $metrics): bool => $metrics['average_accuracy'] === 96)
            ->assertSeeText('96% Phil-IRI score');

        $this->get(route('student.activities'))->assertOk()
            ->assertViewHas('completedSubmissions', fn ($submissions): bool => $submissions->first()->accuracy === 96)
            ->assertSeeText('96%');

        $this->get(route('student.leaderboard'))->assertOk()
            ->assertViewHas('leaderboard', fn ($entries): bool => $entries->firstWhere('student.id', $student->id)['accuracy'] === 96);
    }

    public function test_unmarked_oral_reading_is_not_averaged_as_zero(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        $assessment = $this->oralAssessment($teacher);
        $submission = $this->oralSubmission($assessment, $student);

        $this->assertNull(AssessmentScores::percentage($submission->fresh('assessment')));

        $this->actingAs($teacher)->get(route('reports.index'))->assertOk()
            ->assertViewHas('reportMetrics', fn (array $metrics): bool => $metrics['average_accuracy'] === null)
            ->assertSeeText('No report data yet');

        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()
            ->assertViewHas('studentMetrics', fn (array $metrics): bool => $metrics['average_accuracy'] === null)
            ->assertSeeText('Reading marks needed');
    }

    private function oralAssessment(User $teacher): Assessment
    {
        return Assessment::query()->create([
            'created_by' => $teacher->id,
            'title' => 'Oral Reading Check',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'assessment_type' => 'oral_reading',
            'focus_areas' => ['Reading Fluency'],
            'story_description' => implode(' ', array_fill(0, 100, 'word')),
            'manual_questions' => [],
            'status' => 'published',
        ]);
    }

    private function oralSubmission(Assessment $assessment, User $student, array $overrides = []): AssessmentSubmission
    {
        $result = PhilIri::calculate(array_merge([
            'version' => PhilIri::VERSION,
            'assessment_type' => 'oral_reading',
            'correct_count' => 0,
            'question_count' => 0,
            'word_count' => 100,
            'marked_miscues' => null,
            'miscues' => null,
            'reading_seconds' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ], $overrides));

        return AssessmentSubmission::query()->create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => [],
            'correct_count' => 0,
            'question_count' => 0,
            'points' => 0,
            'possible_points' => 0,
            'phil_iri' => $result,
            'submitted_at' => now(),
        ]);
    }
}
