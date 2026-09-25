<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterventionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_save_and_update_one_plan_per_result_from_both_pages(): void
    {
        $teacher = User::factory()->teacher()->create();
        $submission = $this->submission($teacher);
        $url = route('teacher.interventions.update', $submission);

        $this->actingAs($teacher)->put($url, $this->payload())
            ->assertRedirect(route('reports.student', $submission->user_id).'#intervention-'.$submission->id)
            ->assertSessionHas('intervention_saved', $submission->id);
        $this->assertDatabaseHas('intervention_plans', [
            'assessment_submission_id' => $submission->id,
            'type' => 'guided_practice', 'status' => 'planned',
            'notes' => 'Reread the passage together and discuss the main idea.',
            'follow_up_date' => '2026-10-05',
        ]);

        foreach (['reports.student', 'students.show'] as $route) {
            $response = $this->get(route($route, $submission->user_id))->assertOk()
                ->assertSeeText('Teacher Action Plan')
                ->assertSeeText('Reread the passage together and discuss the main idea.')
                ->assertSee('value="2026-10-05"', false);
            if ($route === 'reports.student') {
                $response->assertSeeText('Action plan saved.');
            }
        }

        $this->put($url, $this->payload(['status' => 'in_progress']))->assertRedirect();
        $this->assertSame('in_progress', $submission->fresh()->interventionPlan->status);

        $payload = $this->payload(['type' => 'enrichment', 'status' => 'done', 'follow_up_date' => null]);
        $payload['return_to'] = 'profile';
        $this->put($url, $payload)
            ->assertRedirect(route('students.show', $submission->user_id).'#intervention-'.$submission->id);
        $this->assertDatabaseCount('intervention_plans', 1);
        $this->assertDatabaseHas('intervention_plans', [
            'assessment_submission_id' => $submission->id,
            'type' => 'enrichment', 'status' => 'done', 'follow_up_date' => null,
        ]);
    }

    public function test_plan_is_linked_to_the_result_and_cannot_be_redirected_to_another_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $submission = $this->submission($teacher);
        $other = $this->submission($teacher);
        $payload = $this->payload([
            'assessment_submission_id' => $other->id,
            'student_id' => $other->user_id, 'teacher_id' => 999,
        ]);

        $this->actingAs($teacher)->put(route('teacher.interventions.update', $submission), $payload)
            ->assertRedirect();
        $this->assertNotNull($submission->fresh()->interventionPlan);
        $this->assertNull($other->fresh()->interventionPlan);
    }

    public function test_other_teachers_cannot_create_change_or_view_the_plan(): void
    {
        $owner = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $submission = $this->submission($owner);
        $url = route('teacher.interventions.update', $submission);

        $this->actingAs($otherTeacher)->put($url, $this->payload())->assertNotFound();
        $this->assertDatabaseCount('intervention_plans', 0);
        $this->actingAs($owner)->put($url, $this->payload())->assertRedirect();
        $this->actingAs($otherTeacher)->put($url, $this->payload(['notes' => 'Unauthorized change']))->assertNotFound();
        $this->assertSame($this->payload()['intervention']['notes'], $submission->fresh()->interventionPlan->notes);
        $this->get(route('reports.student', $submission->user_id))->assertNotFound();
        $this->get(route('students.show', $submission->user_id))
            ->assertOk()->assertDontSeeText($submission->interventionPlan->notes)
            ->assertDontSee($url, false);
    }

    public function test_guests_students_and_admins_cannot_save_plans(): void
    {
        $teacher = User::factory()->teacher()->create();
        $submission = $this->submission($teacher);
        $url = route('teacher.interventions.update', $submission);

        $this->put($url, $this->payload())->assertRedirect(route('login'));
        $this->actingAs($submission->student)->put($url, $this->payload())->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->put($url, $this->payload())->assertForbidden();
        $this->assertDatabaseCount('intervention_plans', 0);
    }

    public function test_invalid_input_preserves_saved_plan_and_only_opens_the_affected_form(): void
    {
        $teacher = User::factory()->teacher()->create();
        $submission = $this->submission($teacher);
        $other = $this->submission($teacher, [], $submission->student);
        $url = route('teacher.interventions.update', $submission);
        $this->actingAs($teacher)->put($url, $this->payload())->assertRedirect();
        $reportUrl = route('reports.student', $submission->user_id);
        $payload = $this->payload([
            'type' => 'invalid', 'status' => 'invalid', 'follow_up_date' => '2026-02-30',
            'notes' => str_repeat('A', 5001),
        ]);

        $this->from($reportUrl)->put($url, $payload)->assertRedirect($reportUrl)
            ->assertSessionHasErrors([
                'intervention.type', 'intervention.status', 'intervention.follow_up_date', 'intervention.notes',
            ], null, 'intervention-'.$submission->id);
        $this->assertSame($this->payload()['intervention']['notes'], $submission->fresh()->interventionPlan->notes);

        $response = $this->get($reportUrl)->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $this->assertTrue($document->getElementById('intervention-'.$submission->id)->hasAttribute('open'));
        $this->assertFalse($document->getElementById('intervention-'.$other->id)->hasAttribute('open'));
        $this->assertSame(str_repeat('A', 5001), $document->getElementById('intervention-'.$submission->id.'-notes')->textContent);
        $this->assertSame('', $document->getElementById('intervention-'.$other->id.'-notes')->textContent);

        $this->put($url, $this->payload(['notes' => '   ']))
            ->assertSessionHasErrors(['intervention.notes'], null, 'intervention-'.$submission->id);
        $this->assertDatabaseCount('intervention_plans', 1);
    }

    public function test_reading_level_suggests_the_type_but_teacher_can_override_it(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $types = [4 => 'enrichment', 3 => 'guided_practice', 1 => 'remediation'];
        $submissions = [];
        foreach ($types as $correct => $type) {
            $submissions[$type] = $this->submission($teacher, ['correct_count' => $correct], $student);
        }
        $numeracy = $this->submission($teacher, [], $student);
        $numeracy->assessment->update(['subject' => 'numeracy']);

        $response = $this->actingAs($teacher)->get(route('reports.student', $student))->assertOk();
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        foreach ($submissions as $type => $submission) {
            $selected = $xpath->query('//select[@id="intervention-'.$submission->id.'-type"]/option[@selected]');
            $this->assertSame($type, $selected->item(0)->getAttribute('value'));
        }
        $this->assertSame(0, $xpath->query('//select[@id="intervention-'.$numeracy->id.'-type"]/option[@selected]')->length);
        $this->assertDatabaseCount('intervention_plans', 0);

        $this->put(route('teacher.interventions.update', $submissions['remediation']), $this->payload(['type' => 'enrichment']))
            ->assertRedirect();
        $this->assertSame('enrichment', $submissions['remediation']->fresh()->interventionPlan->type);
    }

    public function test_notes_are_escaped_and_overdue_follow_up_clears_when_done(): void
    {
        $teacher = User::factory()->teacher()->create();
        $submission = $this->submission($teacher);
        $payload = $this->payload(['notes' => '<script>alert(1)</script>', 'follow_up_date' => now()->subDay()->toDateString()]);

        $this->actingAs($teacher)->put(route('teacher.interventions.update', $submission), $payload)->assertRedirect();
        $this->get(route('reports.student', $submission->user_id))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSeeText('Follow-up overdue:');
        $payload['intervention']['status'] = 'done';
        $this->put(route('teacher.interventions.update', $submission), $payload)->assertRedirect();
        $this->get(route('reports.student', $submission->user_id))->assertOk()->assertDontSeeText('Follow-up overdue:');

        $submission->delete();
        $this->assertDatabaseCount('intervention_plans', 0);
    }

    private function payload(array $overrides = []): array
    {
        return ['return_to' => 'report', 'intervention' => array_merge([
            'type' => 'guided_practice', 'status' => 'planned',
            'notes' => 'Reread the passage together and discuss the main idea.',
            'follow_up_date' => '2026-10-05',
        ], $overrides)];
    }

    private function submission(User $teacher, array $overrides = [], ?User $student = null): AssessmentSubmission
    {
        $assessment = Assessment::create([
            'created_by' => $teacher->id, 'title' => 'Reading Follow-up', 'subject' => 'literacy',
            'quiz_type' => 'multiple_choice', 'delivery_method' => 'manual', 'target_section' => 'all',
            'assessment_type' => 'silent_reading', 'focus_areas' => ['Reading Fluency'],
            'manual_questions' => [], 'status' => 'published',
        ]);

        return AssessmentSubmission::create(array_merge([
            'assessment_id' => $assessment->id, 'user_id' => ($student ?? User::factory()->create())->id,
            'attempt_number' => 1, 'answers' => ['A', 'A', 'A', 'B'],
            'correct_count' => 3, 'question_count' => 4, 'points' => 750, 'possible_points' => 1000,
            'submitted_at' => now(),
        ], $overrides));
    }
}
