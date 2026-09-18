<?php

namespace Tests\Feature;

use App\Models\{Assessment, AssessmentProgress, User, WorksheetAttempt};
use App\Support\NumeracyWorksheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorksheetReadingToolsTest extends TestCase
{
    use RefreshDatabase;

    private function assignment(User $teacher, int $number = 1): Assessment
    {
        return $teacher->createdAssessments()->create(['title' => 'Reading tools', 'subject' => 'numeracy',
            'quiz_type' => 'worksheet', 'delivery_method' => 'manual', 'assessment_type' => 'worksheet',
            'worksheet_number' => $number, 'worksheet_total' => 20, 'target_section' => 'section_a',
            'status' => 'published', 'manual_questions' => [], 'focus_areas' => [], 'retry_limit' => 1]);
    }

    public function test_all_71_parts_have_readable_content_and_diagrams_have_valid_sources(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);
        $parts = 0;
        foreach (NumeracyWorksheets::all() as $worksheet) {
            $this->get(route('worksheets.create', $worksheet['number']))->assertOk()->assertSee('worksheet-text-page', false);
            foreach ($worksheet['pages'] as $page) {
                $parts++;
                $this->assertNotEmpty($page['reading']['title']);
                $this->assertNotEmpty($page['reading']['sections']);
                foreach ($page['reading']['sections'] as $section) foreach ($section['items'] ?? [] as $item) {
                    if (!is_array($item)) { $this->assertNotSame('', $item); continue; }
                    $this->assertNotEmpty($item['text']);
                    if (isset($item['figure'])) $this->assertFileExists(resource_path('worksheets/aral-g6/'.$item['figure']));
                    if (isset($item['grid'])) $this->assertGreaterThan(0, $item['grid'][0] * $item['grid'][1]);
                }
            }
        }
        $this->assertSame(71, $parts);
        $this->assertSame('6 + (10 x 14)', NumeracyWorksheets::find(5)['pages'][0]['reading']['sections'][0]['items'][1]);
        $this->assertSame('2 1/2 x 4 2/5', NumeracyWorksheets::find(16)['pages'][1]['reading']['sections'][1]['items'][0]);
        $this->assertSame(48, collect(NumeracyWorksheets::find(29)['pages'])->sum(fn ($page) => collect($page['reading']['sections'])->sum(fn ($section) => count($section['items'] ?? []))));
    }

    public function test_only_the_assessment_teachers_password_unlocks_the_current_attempt(): void
    {
        $teacher = User::factory()->teacher()->create(['password' => 'Only-Teacher-Secret!']);
        User::factory()->teacher()->create(['password' => 'Another-Teacher-Secret!']);
        $student = User::factory()->create(['section' => 'Section A', 'password' => 'Student-Secret!']);
        $assignment = $this->assignment($teacher);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $url = route('worksheets.table', $assignment);
        $payload = ['attempt_key' => $progress->attempt_key];
        $this->actingAs($student)->get(route('student.assessments.show', $assignment))->assertOk()
            ->assertSee('Divisibility Rules Review')->assertSee('Worksheet text')->assertDontSee('Only-Teacher-Secret!')->assertDontSee($teacher->password);
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson($url, $payload + ['password' => 'Student-Secret!'])->assertUnprocessable();
        $this->postJson($url, $payload + ['password' => 'Another-Teacher-Secret!'])->assertUnprocessable();
        $this->assertNull($progress->fresh()->multiplication_table_unlocked_at);
        $response = $this->postJson($url, $payload + ['password' => 'Only-Teacher-Secret!'])->assertOk()
            ->assertJsonPath('table.11.11', 144)->assertJsonPath('table.6.7', 56);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertNotNull($progress->fresh()->multiplication_table_unlocked_at);
        $this->assertSame([], $progress->fresh()->state);
        $this->postJson($url, $payload)->assertOk();
        $next = AssessmentProgress::forAttempt($assignment, $student, 2);
        $this->assertNull($next->multiplication_table_unlocked_at);
        $this->travel(61)->seconds();
        $this->postJson($url, ['attempt_key' => $next->attempt_key])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson($url, ['attempt_key' => $next->attempt_key, 'password' => 'Only-Teacher-Secret!'])->assertOk();
    }

    public function test_password_attempts_are_rate_limited_and_client_state_cannot_grant_access(): void
    {
        $teacher = User::factory()->teacher()->create(['password' => 'Never-Sent-Secret!']);
        $student = User::factory()->create(['section' => 'Section A']);
        $assignment = $this->assignment($teacher);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $this->actingAs($student);
        $this->postJson(route('worksheets.save', $assignment), ['attempt_key' => $progress->attempt_key, 'revision' => 1,
            'page' => 0, 'tableAllowed' => true, 'multiplication_table_unlocked_at' => now(),
            'pages' => [['text' => '', 'strokes' => []], ['text' => '', 'strokes' => []]]])->assertOk();
        $this->assertNull($progress->fresh()->multiplication_table_unlocked_at);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('worksheets.table', $assignment), ['attempt_key' => $progress->attempt_key, 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson(route('worksheets.table', $assignment), ['attempt_key' => $progress->attempt_key, 'password' => 'Never-Sent-Secret!'])->assertStatus(429);
        $this->assertNull($progress->fresh()->multiplication_table_unlocked_at);
    }

    public function test_table_is_private_locked_after_submission_and_unavailable_for_locked_assignments(): void
    {
        $teacher = User::factory()->teacher()->create(['password' => 'Teacher-Secret!']);
        $student = User::factory()->create(['section' => 'Section A']);
        $assignment = $this->assignment($teacher);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $progress->forceFill(['multiplication_table_unlocked_at' => now()])->save();
        $payload = ['attempt_key' => $progress->attempt_key];
        $url = route('worksheets.table', $assignment);
        $this->actingAs(User::factory()->create(['section' => 'Section A']))->postJson($url, $payload)->assertNotFound();
        $this->actingAs($teacher)->postJson($url, $payload)->assertForbidden();
        $this->actingAs($student);
        $assignment->update(['status' => 'draft']);
        $this->postJson($url, $payload)->assertNotFound();
        $assignment->update(['status' => 'published']);
        $attempt = WorksheetAttempt::create(['assessment_id' => $assignment->id, 'user_id' => $student->id, 'progress_id' => $progress->id, 'total' => 20, 'pages' => []]);
        $this->postJson($url, $payload)->assertStatus(409);
        $this->get(route('worksheets.review', $attempt))->assertOk()->assertDontSee('data-book-table-form', false);
    }

    public function test_fraction_models_save_and_invalid_model_cells_are_rejected(): void
    {
        $student = User::factory()->create(['section' => 'Section A']);
        $assignment = $this->assignment(User::factory()->teacher()->create(), 14);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $payload = ['attempt_key' => $progress->attempt_key, 'revision' => 1, 'page' => 0, 'step' => 'answer', 'pages' => [
            ['text' => '', 'strokes' => [], 'shading' => ['0-0' => [0, 1, 2]]],
            ['text' => '5. 1/3', 'strokes' => []],
        ]];
        $this->actingAs($student)->postJson(route('worksheets.save', $assignment), $payload)->assertOk();
        $this->assertSame([0, 1, 2], $progress->fresh()->state['pages'][0]['shading']['0-0']);
        $bad = $payload; $bad['pages'][0]['shading']['0-0'] = [99];
        $this->postJson(route('worksheets.save', $assignment), $bad)->assertUnprocessable();
        $bad['pages'][0]['shading'] = ['9-9' => [0]];
        $this->postJson(route('worksheets.save', $assignment), $bad)->assertUnprocessable();
        $this->postJson(route('worksheets.submit', $assignment), $payload)->assertOk();
        $this->assertSame([0, 1, 2], WorksheetAttempt::firstOrFail()->pages[0]['shading']['0-0']);
    }

    public function test_reading_only_parts_need_no_fabricated_answers_and_figures_are_authorized(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        $assignment = $this->assignment($teacher, 3);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $this->actingAs($student)->postJson(route('worksheets.submit', $assignment), ['attempt_key' => $progress->attempt_key,
            'revision' => 1, 'page' => 1, 'pages' => [['text' => '', 'strokes' => []], ['text' => '1. Yes; 160 is even.', 'strokes' => []]]])->assertOk();
        $image = route('worksheets.image', [33, 1, 'figure' => 'thermometer-1.jpeg']);
        $this->get($image)->assertNotFound();
        $this->assignment($teacher, 33);
        $this->get($image)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get(route('worksheets.image', [33, 1, 'figure' => '../attribution.pdf']))->assertNotFound();
        $this->get(route('worksheets.image', [33, 2, 'figure' => 'thermometer-1.jpeg']))->assertNotFound();
    }
}
