<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\InterventionFollowUp;
use App\Support\PhilIri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterventionFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Assessment $assessment;
    private AssessmentSubmission $baseline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfSecond());
        $this->teacher = User::factory()->teacher()->create();
        $this->student = User::factory()->create(['section' => 'Section A']);
        $this->assessment = $this->assessment();
        $this->baseline = $this->submission($this->assessment, ['submitted_at' => now()->subDay()]);
    }

    public function test_link_records_next_student_submission_and_keeps_it_when_student_retries(): void
    {
        $earlier = $this->submission($this->assessment, ['attempt_number' => 2]);
        $this->link();
        $this->assertNull($this->baseline->fresh()->interventionPlan->follow_up_submission_id);
        $this->assertSame('Awaiting result', InterventionFollowUp::status($this->baseline->fresh()->interventionPlan)['label']);

        $this->actingAs($this->student)->postJson(route('student.assessments.submit', $this->assessment), [
            'answers' => ['A', 'A', 'A', 'B'],
        ])->assertOk()->assertJsonPath('correct_count', 3);
        $plan = $this->baseline->fresh()->interventionPlan;
        $followUpId = $plan->follow_up_submission_id;
        $this->assertNotNull($followUpId);
        $this->assertNotSame($earlier->id, $followUpId);
        $this->assertSame('in_progress', $plan->status);
        $comparison = InterventionFollowUp::comparison($plan, $this->baseline);
        $this->assertEquals(25, $comparison['difference']);
        $this->assertSame('Frustration', $comparison['before']['level']);
        $this->assertSame('Instructional', $comparison['after']['level']);

        $this->postJson(route('student.assessments.submit', $this->assessment), ['answers' => ['A', 'A', 'A', 'A']])->assertOk();
        $this->assertSame($followUpId, $plan->fresh()->follow_up_submission_id);
        $this->link(['notes' => 'Follow-up discussion recorded.']);
        $this->assertSame($followUpId, $plan->fresh()->follow_up_submission_id);

        foreach (['reports.student', 'students.show'] as $route) {
            $this->actingAs($this->teacher)->get(route($route, $this->student))->assertOk()
                ->assertSeeText('Follow-up completed')->assertSeeText('+25 percentage points')
                ->assertSeeText('Follow-up: Instructional')->assertSeeText('View Follow-up Result');
        }
    }

    public function test_other_students_other_assessments_and_older_submissions_are_not_matched(): void
    {
        $this->link();
        $this->submission($this->assessment, ['user_id' => User::factory()->create()->id]);
        $this->submission($this->assessment());
        $this->submission($this->assessment, ['submitted_at' => now()->subMinute()]);
        $this->assertNull($this->baseline->fresh()->interventionPlan->follow_up_submission_id);
        $next = $this->submission($this->assessment, ['attempt_number' => 2]);
        $this->assertSame($next->id, $this->baseline->fresh()->interventionPlan->follow_up_submission_id);
    }

    public function test_different_assessment_requires_comparison_basis_and_resets_only_when_relinked(): void
    {
        $other = $this->assessment(['title' => 'Comparable passage']);
        $this->actingAs($this->teacher)->put($this->url(), $this->payload(['follow_up_assessment_id' => $other->id]))
            ->assertSessionHasErrors(['intervention.comparison_basis'], null, 'intervention-'.$this->baseline->id);
        $this->assertNull($this->baseline->fresh()->interventionPlan);
        $this->link();
        $original = $this->submission($this->assessment, ['attempt_number' => 2]);
        $oldOther = $this->submission($other);
        $this->travel(2)->minutes();
        $this->link(['follow_up_assessment_id' => $other->id, 'comparison_basis' => 'Same length, reading skills and difficulty.']);
        $plan = $this->baseline->fresh()->interventionPlan;
        $this->assertNull($plan->follow_up_submission_id);
        $this->assertTrue($plan->follow_up_linked_at->eq(now()));
        $next = $this->submission($other, ['attempt_number' => 2, 'correct_count' => 4]);
        $this->assertSame($next->id, $plan->fresh()->follow_up_submission_id);
        $this->assertNotSame($original->id, $plan->fresh()->follow_up_submission_id);
        $this->assertNotSame($oldOther->id, $plan->fresh()->follow_up_submission_id);
        $this->link(['follow_up_assessment_id' => null]);
        $plan = $plan->fresh();
        $this->assertNull($plan->follow_up_assessment_id);
        $this->assertNull($plan->follow_up_submission_id);
        $this->assertNull($plan->follow_up_linked_at);
        $this->assertNull($plan->comparison_basis);
    }

    public function test_cannot_link_foreign_locked_wrong_section_or_incompatible_assessments(): void
    {
        $targets = [
            $this->assessment(['created_by' => User::factory()->teacher()->create()->id]),
            $this->assessment(['status' => 'draft']),
            $this->assessment(['target_section' => 'section_b']),
            $this->assessment(['subject' => 'numeracy']),
            $this->assessment(['assessment_type' => 'listening_comprehension']),
        ];
        foreach ($targets as $target) {
            $this->actingAs($this->teacher)->put($this->url(), $this->payload([
                'follow_up_assessment_id' => $target->id, 'comparison_basis' => 'Comparable.',
            ]))->assertSessionHasErrors(['intervention.follow_up_assessment_id'], null, 'intervention-'.$this->baseline->id);
        }
        $this->assertNull($this->baseline->fresh()->interventionPlan);
        $this->actingAs(User::factory()->teacher()->create())->put($this->url(), $this->payload())->assertNotFound();
        $this->actingAs($this->student)->put($this->url(), $this->payload())->assertForbidden();
    }

    public function test_comparison_basis_cannot_be_cleared_while_a_different_assessment_is_linked(): void
    {
        $other = $this->assessment();
        $this->link(['follow_up_assessment_id' => $other->id, 'comparison_basis' => 'Same skills and difficulty.']);
        $this->put($this->url(), $this->payload(['follow_up_assessment_id' => $other->id, 'comparison_basis' => null]))
            ->assertSessionHasErrors(['intervention.comparison_basis'], null, 'intervention-'.$this->baseline->id);
        $this->assertSame('Same skills and difficulty.', $this->baseline->fresh()->interventionPlan->comparison_basis);
    }

    public function test_saved_locked_target_does_not_prevent_updating_plan_notes_and_cannot_spoof_result(): void
    {
        $this->link();
        $this->assessment->update(['status' => 'draft']);
        $this->link(['notes' => 'Awaiting republishing.', 'follow_up_submission_id' => $this->baseline->id]);
        $plan = $this->baseline->fresh()->interventionPlan;
        $this->assertSame('Awaiting republishing.', $plan->notes);
        $this->assertNull($plan->follow_up_submission_id);
        $this->get(route('reports.student', $this->student))->assertOk()->assertSeeText('(unavailable)');
    }

    public function test_oral_comparison_uses_saved_marks_and_displays_word_counts_and_comprehension(): void
    {
        $this->assessment->update(['assessment_type' => 'oral_reading', 'story_description' => implode(' ', array_fill(0, 100, 'word'))]);
        $this->baseline->update(['phil_iri' => PhilIri::initial($this->assessment, 2, 4, [
            'word_marks' => array_merge(array_fill(0, 10, 2), array_fill(0, 90, 0)),
        ])]);
        $other = $this->assessment(['assessment_type' => 'oral_reading', 'story_description' => implode(' ', array_fill(0, 150, 'word'))]);
        $this->link(['follow_up_assessment_id' => $other->id, 'comparison_basis' => 'Same vocabulary and sentence difficulty.']);
        $this->submission($other, ['phil_iri' => PhilIri::initial($other, 4, 4, [
            'word_marks' => array_merge(array_fill(0, 3, 2), array_fill(0, 147, 0)),
        ])]);
        $this->get(route('reports.student', $this->student))->assertOk()
            ->assertSeeText('+8 percentage points')->assertSeeText('Original: 10 / 100 words')
            ->assertSeeText('Follow-up: 3 / 150 words')->assertSeeText('Original: Frustration')
            ->assertSeeText('Follow-up: Independent')->assertSeeText('Follow-up: 100%');
    }

    public function test_missing_marks_are_not_reported_as_zero_or_improvement(): void
    {
        $this->assessment->update(['assessment_type' => 'oral_reading']);
        $this->link();
        $this->submission($this->assessment, ['attempt_number' => 2]);
        $comparison = InterventionFollowUp::comparison($this->baseline->fresh()->interventionPlan, $this->baseline->fresh());
        $this->assertNull($comparison['difference']);
        $this->get(route('reports.student', $this->student))->assertOk()
            ->assertSeeText('Comparison pending')->assertSeeText('Not recorded')->assertDontSeeText('percentage points');
    }

    public function test_queue_shows_due_and_overdue_plans_only_for_the_owner_and_clears_when_completed(): void
    {
        $this->link(['follow_up_date' => today()->toDateString()]);
        $this->assertSame('Due today', InterventionFollowUp::status($this->baseline->fresh()->interventionPlan)['label']);
        $this->travel(1)->days();
        $this->get(route('reports.index'))->assertOk()->assertSeeText('Intervention Follow-ups')
            ->assertSeeText('Overdue')->assertSee(route('reports.student', $this->student).'#intervention-'.$this->baseline->id, false);
        $this->actingAs(User::factory()->teacher()->create())->get(route('reports.index'))->assertOk()
            ->assertDontSeeText($this->student->name)->assertSeeText('No intervention follow-ups yet.');
        $this->submission($this->assessment, ['attempt_number' => 2, 'correct_count' => 3]);
        $this->actingAs($this->teacher)->get(route('reports.index'))->assertOk()
            ->assertSeeText('Follow-up completed')->assertDontSeeText('Overdue');
    }

    public function test_numeracy_worksheets_require_the_same_worksheet_and_show_score_without_reading_level(): void
    {
        $this->assessment->update(['subject' => 'numeracy', 'worksheet_number' => 1]);
        $wrongWorksheet = $this->assessment(['subject' => 'numeracy', 'worksheet_number' => 2]);
        $this->actingAs($this->teacher)->put($this->url(), $this->payload([
            'follow_up_assessment_id' => $wrongWorksheet->id, 'comparison_basis' => 'Different worksheet.',
        ]))->assertSessionHasErrors(['intervention.follow_up_assessment_id'], null, 'intervention-'.$this->baseline->id);
        $this->link();
        $this->submission($this->assessment, ['attempt_number' => 2, 'correct_count' => 3]);
        $comparison = InterventionFollowUp::comparison($this->baseline->fresh()->interventionPlan, $this->baseline->fresh());
        $this->assertEquals(25, $comparison['difference']);
        $this->assertSame('Assessment score', $comparison['before']['score_label']);
        $this->assertNull($comparison['before']['level']);
        $this->get(route('reports.student', $this->student))->assertOk()->assertSeeText('+25 percentage points');
    }

    public function test_changing_assessment_type_suppresses_invalid_comparison(): void
    {
        $this->link();
        $followUp = $this->submission($this->assessment, ['attempt_number' => 2, 'phil_iri' => PhilIri::initial($this->assessment, 3, 4)]);
        $this->assessment->update(['assessment_type' => 'listening_comprehension']);
        $this->get(route('reports.student', $this->student))->assertOk()->assertSeeText('These results cannot currently be compared.')
            ->assertDontSeeText('+25 percentage points');
        $followUp->delete();
        $this->assertNull($this->baseline->fresh()->interventionPlan->follow_up_submission_id);
    }

    private function url(): string
    {
        return route('teacher.interventions.update', $this->baseline);
    }

    private function payload(array $overrides = []): array
    {
        return ['return_to' => 'report', 'intervention' => array_merge([
            'type' => 'guided_practice', 'notes' => 'Discuss the main idea together.', 'status' => 'in_progress',
            'follow_up_date' => now()->addWeek()->toDateString(), 'follow_up_assessment_id' => $this->assessment->id,
            'comparison_basis' => null,
        ], $overrides)];
    }

    private function link(array $overrides = []): void
    {
        $this->actingAs($this->teacher)->put($this->url(), $this->payload($overrides))
            ->assertSessionHasNoErrors()->assertRedirect();
    }

    private function assessment(array $overrides = []): Assessment
    {
        return Assessment::create(array_merge([
            'created_by' => $this->teacher->id, 'title' => 'Reading Follow-up', 'subject' => 'literacy',
            'quiz_type' => 'multiple_choice', 'delivery_method' => 'manual', 'target_section' => 'all',
            'assessment_type' => 'silent_reading', 'status' => 'published', 'retry_limit' => 10,
            'manual_questions' => array_fill(0, 4, [
                'question' => 'What happened?', 'answers' => ['A' => 'Yes', 'B' => 'No', 'C' => 'Maybe', 'D' => 'Never'], 'correct_answer' => 'A',
            ]),
        ], $overrides));
    }

    private function submission(Assessment $assessment, array $overrides = []): AssessmentSubmission
    {
        return AssessmentSubmission::create(array_merge([
            'assessment_id' => $assessment->id, 'user_id' => $this->student->id, 'attempt_number' => 1,
            'answers' => ['A', 'A', 'B', 'B'], 'correct_count' => 2, 'question_count' => 4,
            'points' => 500, 'possible_points' => 1000, 'submitted_at' => now(),
        ], $overrides));
    }
}
