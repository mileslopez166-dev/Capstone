<?php

namespace Tests\Feature;

use App\Models\{Assessment, AssessmentProgress, User, WorksheetAttempt};
use App\Support\NumeracyWorksheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorksheetResponseFormatTest extends TestCase
{
    use RefreshDatabase;

    private function assignment(User $teacher, int $number): Assessment
    {
        return $teacher->createdAssessments()->create(['title' => 'Format '.$number, 'subject' => 'numeracy',
            'quiz_type' => 'worksheet', 'worksheet_number' => $number, 'worksheet_total' => 20,
            'target_section' => 'section_a', 'status' => 'published', 'manual_questions' => [], 'focus_areas' => []]);
    }

    private function payload(AssessmentProgress $progress, array $worksheet): array
    {
        return ['attempt_key' => $progress->attempt_key, 'revision' => 1, 'page' => 0, 'step' => 'answer',
            'pages' => array_map(fn ($page) => ['text' => '', 'strokes' => [], 'responses' => []], $worksheet['pages'])];
    }

    public function test_every_question_has_a_specific_format_and_all_formats_save_submit_and_review(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        $types = [];
        foreach (NumeracyWorksheets::all() as $worksheet) {
            $assignment = $this->assignment($teacher, $worksheet['number']);
            $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
            $payload = $this->payload($progress, $worksheet);
            foreach ($worksheet['pages'] as $pageIndex => $page) {
                $questionCount = collect($page['reading']['sections'])->sum(fn ($section) => count($section['items'] ?? []));
                $this->assertCount($questionCount, $page['responses']);
                foreach ($page['responses'] as $key => $definition) {
                    $this->assertNotEmpty($definition['fields']);
                    foreach ($definition['fields'] as $field) {
                        $types[$field['type']] = true;
                        $payload['pages'][$pageIndex]['responses'][$key][$field['key']] = match ($field['type']) {
                            'checkbox' => [$field['options'][0]], 'select', 'radio' => $field['options'][0],
                            'drawing' => [['color' => '#174d97', 'width' => 3, 'points' => [[0.1, 0.2], [0.5, 0.8]]]],
                            'digit', 'integer' => '1', 'temperature' => '0', 'number' => '1234.5', default => 'Student work',
                        };
                    }
                }
            }
            $this->actingAs($student)->postJson(route('worksheets.save', $assignment), $payload)->assertOk();
            $this->assertEquals($payload['pages'], $progress->fresh()->state['pages']);
            $this->get(route('student.assessments.show', $assignment))->assertOk()->assertSee('data-item-response', false)->assertDontSee('data-book-add-answer', false);
            $response = $this->postJson(route('worksheets.submit', $assignment), $payload)->assertOk();
            $attempt = WorksheetAttempt::where('progress_id', $progress->id)->firstOrFail();
            $this->assertEquals($payload['pages'], $attempt->pages);
            $this->actingAs($teacher)->get($response->json('url'))->assertOk()->assertSee('data-readonly="true"', false);
        }
        foreach (['checkbox', 'radio', 'digit', 'drawing', 'integer', 'number', 'select', 'textarea', 'temperature'] as $type) $this->assertArrayHasKey($type, $types);
    }

    public function test_divisibility_and_other_worksheet_formats_match_the_instructions(): void
    {
        $one = NumeracyWorksheets::find(1);
        $this->assertSame(['2', '3', '5', '9', '10'], $one['pages'][0]['responses']['1-0']['fields'][0]['options']);
        $this->assertSame('digit', $one['pages'][1]['responses']['1-0']['fields'][0]['type']);
        $this->assertSame(['choice', 'reason'], array_column(NumeracyWorksheets::find(3)['pages'][1]['responses']['0-0']['fields'], 'key'));
        $this->assertSame(['product', 'gcf', 'lcm'], array_column(NumeracyWorksheets::find(11)['pages'][1]['responses']['0-0']['fields'], 'key'));
        $this->assertSame('drawing', NumeracyWorksheets::find(25)['pages'][0]['responses']['0-5']['fields'][0]['type']);
        $this->assertSame(['Regular', 'Irregular'], NumeracyWorksheets::find(26)['pages'][0]['responses']['1-0']['fields'][0]['options']);
        $this->assertSame(['hour', 'minute', 'period'], array_column(NumeracyWorksheets::find(29)['pages'][0]['responses']['1-0']['fields'], 'key'));
    }

    public function test_invalid_answers_and_forged_controls_are_rejected_without_overwriting_work(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        $this->actingAs($student);
        $cases = [
            [1, 0, '1-0', ['divisors' => ['7']]], [1, 0, '1-0', ['divisors' => ['2', '2']]],
            [1, 0, '9-9', ['divisors' => ['2']]], [1, 0, '1-0', ['score' => '100']],
            [1, 1, '1-0', ['digit' => '12']], [3, 1, '0-0', ['choice' => 'Maybe']],
            [14, 0, '0-0', ['denominator' => '0']], [27, 0, '0-0', ['hour' => '24']],
            [29, 0, '1-0', ['hour' => '0']], [29, 0, '1-0', ['period' => 'Evening']],
            [34, 0, '0-0', ['temperature' => '51']],
            [25, 0, '0-5', ['drawing' => [['color' => '#174d97', 'width' => 3, 'points' => [[2, 0]]]]]],
        ];
        foreach ($cases as [$number, $part, $key, $answer]) {
            $assignment = $this->assignment($teacher, $number);
            $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
            $payload = $this->payload($progress, NumeracyWorksheets::find($number));
            $payload['pages'][$part]['responses'][$key] = $answer;
            $this->postJson(route('worksheets.save', $assignment), $payload)->assertUnprocessable();
            $this->assertSame([], $progress->fresh()->state);
        }
    }

    public function test_empty_controls_do_not_count_but_zero_no_and_saved_legacy_answers_are_retained(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Section A']);
        $assignment = $this->assignment($teacher, 4);
        $progress = AssessmentProgress::forAttempt($assignment, $student, 1);
        $payload = $this->payload($progress, NumeracyWorksheets::find(4));
        $payload['pages'][0]['responses'] = ['0-0' => ['choice' => ''], '1-0' => ['divisors' => []]];
        $payload['pages'][1]['responses'] = ['0-0' => ['divisors' => []]];
        $this->actingAs($student)->postJson(route('worksheets.save', $assignment), $payload)->assertOk();
        $this->postJson(route('worksheets.submit', $assignment), $payload)->assertUnprocessable();
        $payload['revision'] = 2;
        $payload['pages'][0]['responses']['0-0']['choice'] = 'No';
        $payload['pages'][1]['answers'] = [['label' => '21', 'answer' => '2, 3, 4, 6, 8, 9, 12']];
        $this->postJson(route('worksheets.submit', $assignment), $payload)->assertOk();
        $this->assertEquals($payload['pages'], WorksheetAttempt::firstOrFail()->pages);
        $this->assertTrue(\App\Support\WorksheetResponses::hasAnswer(['0-0' => ['answer' => '0']]));
    }
}
