<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\PhilIri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhilIriScoringTest extends TestCase
{
    use RefreshDatabase;

    private function assessment(User $teacher, string $type = 'silent_reading', int $count = 5, string $subject = 'literacy'): Assessment
    {
        return Assessment::create([
            'created_by' => $teacher->id, 'title' => 'Reading Check', 'subject' => $subject,
            'assessment_type' => $type, 'quiz_type' => 'multiple_choice', 'delivery_method' => 'manual',
            'target_section' => 'all', 'status' => 'published', 'retry_limit' => 2,
            'story_description' => implode(' ', array_fill(0, 100, 'word')),
            'manual_questions' => array_fill(0, $count, [
                'question' => 'What helps a plant grow?',
                'answers' => ['A' => 'Sunlight', 'B' => 'A shoe', 'C' => 'Plastic', 'D' => 'Glass'], 'correct_answer' => 'A',
            ]),
        ]);
    }

    private function submit(Assessment $assessment, User $student, int $correct, array $extra = [])
    {
        $count = count($assessment->manual_questions);
        return $this->actingAs($student)->postJson(route('student.assessments.submit', $assessment), array_merge([
            'answers' => array_merge(array_fill(0, $correct, 'A'), array_fill(0, $count - $correct, 'B')),
        ], $extra));
    }

    public function test_literacy_result_is_saved_and_shared_by_student_and_teacher_without_changing_points(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher);
        $this->submit($assessment, $student, 4)->assertOk()->assertJsonPath('phil_iri.level', 'Independent')
            ->assertJsonPath('points', 1000)->assertJsonPath('possible_points', 1250);
        $submission = AssessmentSubmission::firstOrFail();
        $this->assertSame(PhilIri::VERSION, $submission->phil_iri['version']);
        $this->assertSame('Independent', $submission->phil_iri['comprehension_level']);
        $interpretation = $submission->phil_iri['comprehension_interpretation'];
        $this->assertStringContainsString('strong understanding', $interpretation);
        $this->actingAs($student)->get(route('student.activities'))->assertOk()->assertSee('Phil-IRI-based result')->assertSee('Independent')->assertSee('Comprehension interpretation')->assertSee($interpretation);
        $this->actingAs($teacher)->get(route('reports.student', $student))->assertOk()->assertSee('Phil-IRI scoring')->assertSee('Independent')->assertSee($interpretation);
        $this->get(route('students.show', $student))->assertOk()->assertSee($interpretation);
        $this->get(route('teacher.phil-iri.show', $submission))->assertOk()->assertSee('80% and above')->assertSee($interpretation);
    }

    public function test_listening_uses_comprehension_without_word_reading_or_speed(): void
    {
        $assessment = $this->assessment(User::factory()->teacher()->create(), 'listening_comprehension');
        $this->submit($assessment, User::factory()->create(), 3)->assertOk()
            ->assertJsonPath('phil_iri.level', 'Instructional')
            ->assertJsonPath('phil_iri.measure', 'Listening comprehension')
            ->assertJsonPath('phil_iri.word_reading_percent', null)->assertJsonPath('phil_iri.words_per_minute', null);
    }

    public function test_numeracy_keeps_existing_scoring_and_has_no_phil_iri_result(): void
    {
        $teacher = User::factory()->teacher()->create();
        $assessment = $this->assessment($teacher, 'silent_reading', 5, 'numeracy');
        $student = User::factory()->create();
        $this->submit($assessment, $student, 3)->assertOk()->assertJsonPath('phil_iri', null)->assertJsonPath('points', 750);
        $this->get(route('student.activities'))->assertOk()->assertDontSee('Phil-IRI-based result');
        $this->actingAs($teacher)->get(route('teacher.phil-iri.show', AssessmentSubmission::firstOrFail()))->assertNotFound();
    }

    public function test_oral_highlights_cannot_issue_a_verified_grade_and_teacher_can_finish_scoring(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher, 'oral_reading', 0);
        $this->submit($assessment, $student, 0, [
            'phil_iri' => ['level' => 'Independent', 'miscues' => 0, 'reviewed_by' => $teacher->id],
            'revision' => 1,
            'state' => ['answers' => [], 'phase' => 'finished', 'reading_seconds' => 90, 'timer_status' => 'finished', 'word_marks' => [1, 2, 0]],
        ])->assertOk()->assertJsonPath('phil_iri.status', 'awaiting_teacher')->assertJsonPath('phil_iri.level', null)
            ->assertJsonPath('phil_iri.miscues', null)->assertJsonPath('phil_iri.comprehension_percent', null);
        $submission = AssessmentSubmission::firstOrFail();
        $this->actingAs($teacher)->patch(route('teacher.phil-iri.update', $submission), [
            'word_count' => 100, 'miscues' => 4, 'reading_seconds' => 120,
            'comprehension_correct' => 4, 'comprehension_questions' => 5,
        ])->assertRedirect(route('teacher.phil-iri.show', $submission));
        $result = $submission->fresh()->phil_iri;
        $this->assertSame('Instructional', $result['level']);
        $this->assertStringContainsString('strong understanding', $result['comprehension_interpretation']);
        $this->assertEquals(96, $result['word_reading_percent']);
        $this->assertEquals(80, $result['comprehension_percent']);
        $this->assertEquals(50, $result['words_per_minute']);
        $this->assertSame($teacher->id, $result['reviewed_by']);
        $this->assertNotNull($result['reviewed_at']);
        $this->assertEquals(0, $submission->fresh()->points);
        $this->assertEquals(0, $submission->fresh()->question_count);
        $this->actingAs($student)->get(route('student.activities'))->assertOk()->assertSee('Instructional')->assertSee('96%');
    }

    public function test_missing_comprehension_is_not_treated_as_zero_and_zero_is_not_nonreader(): void
    {
        $teacher = User::factory()->teacher()->create();
        $assessment = $this->assessment($teacher, 'oral_reading', 0);
        $this->submit($assessment, User::factory()->create(), 0)->assertOk();
        $submission = AssessmentSubmission::firstOrFail();
        $route = route('teacher.phil-iri.update', $submission);
        $this->actingAs($teacher)->patch($route, ['word_count' => 100, 'miscues' => 0])->assertRedirect();
        $this->assertSame('incomplete', $submission->fresh()->phil_iri['status']);
        $this->assertNull($submission->fresh()->phil_iri['level']);
        $this->assertNull($submission->fresh()->phil_iri['words_per_minute']);
        $this->patch($route, ['word_count' => 100, 'miscues' => 100, 'comprehension_correct' => 0, 'comprehension_questions' => 5])->assertRedirect();
        $this->assertSame('Frustration', $submission->fresh()->phil_iri['level']);
    }

    public function test_red_marks_generate_a_saved_provisional_grade_and_prefill_teacher_scoring(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher, 'oral_reading', 0);
        $this->submit($assessment, $student, 0, [
            'revision' => 1,
            'state' => ['answers' => [], 'phase' => 'finished', 'reading_seconds' => 0, 'timer_status' => 'idle',
                'word_marks' => array_merge(array_fill(0, 4, 2), array_fill(0, 96, 0))],
        ])->assertOk()->assertJsonPath('phil_iri.marked_miscues', 4)
            ->assertJsonPath('phil_iri.word_reading_percent', 96)->assertJsonPath('phil_iri.word_reading_level', 'Instructional')
            ->assertJsonPath('phil_iri.word_reading_provisional', true)->assertJsonPath('phil_iri.level', null)
            ->assertJsonPath('phil_iri.reviewed_by', null)->assertJsonPath('points', 0);
        $submission = AssessmentSubmission::firstOrFail();
        $snapshot = $submission->phil_iri;
        $this->assertSame(4, $snapshot['marked_miscues']);
        $this->get(route('student.activities'))->assertOk()->assertSee('96%')->assertSee('Provisional word-reading score');
        $this->actingAs($teacher)->get(route('teacher.phil-iri.show', $submission))->assertOk()
            ->assertSee('name="miscues" type="number" min="0" max="10000" value="4"', false);
        // Subsequent content edits must not regrade the already submitted marks.
        $assessment->update(['story_description' => 'A changed passage.']);
        $this->assertSame($snapshot, PhilIri::forSubmission($submission->fresh()));
        $this->patch(route('teacher.phil-iri.update', $submission), ['word_count' => 100, 'miscues' => 2])->assertRedirect();
        $result = $submission->fresh()->phil_iri;
        $this->assertSame(4, $result['marked_miscues']);
        $this->assertSame(2, $result['miscues']);
        $this->assertEquals(98, $result['word_reading_percent']);
        $this->assertFalse($result['word_reading_provisional']);
        $this->assertNull($result['level']);
        $this->assertSame('incomplete', $result['status']);
    }

    public function test_word_grade_boundaries_use_complete_saved_red_marks(): void
    {
        $assessment = $this->assessment(User::factory()->teacher()->create(), 'oral_reading', 0);
        foreach ([0 => 'Independent', 3 => 'Independent', 4 => 'Instructional', 10 => 'Instructional', 11 => 'Frustration', 100 => 'Frustration'] as $errors => $level) {
            $this->submit($assessment, User::factory()->create(), 0, [
                'revision' => 1,
                'state' => ['answers' => [], 'phase' => 'finished', 'reading_seconds' => 0, 'timer_status' => 'idle',
                    'word_marks' => array_merge(array_fill(0, $errors, 2), array_fill(0, 100 - $errors, 0))],
            ])->assertOk()->assertJsonPath('phil_iri.marked_miscues', $errors)
                ->assertJsonPath('phil_iri.word_reading_percent', 100 - $errors)
                ->assertJsonPath('phil_iri.word_reading_level', $level)
                ->assertJsonPath('phil_iri.word_reading_provisional', true)
                ->assertJsonPath('phil_iri.status', 'awaiting_teacher');
        }
    }

    public function test_only_assessment_owner_can_read_and_update_oral_grading(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $this->submit($this->assessment($teacher, 'oral_reading', 0), $student, 0)->assertOk();
        $submission = AssessmentSubmission::firstOrFail();
        $data = ['word_count' => 100, 'miscues' => 0];
        foreach ([User::factory()->create(), $student, User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('teacher.phil-iri.show', $submission))->assertForbidden();
            $this->patchJson(route('teacher.phil-iri.update', $submission), $data)->assertForbidden();
        }
        $this->actingAs(User::factory()->teacher()->create())->get(route('teacher.phil-iri.show', $submission))->assertNotFound();
        $this->patchJson(route('teacher.phil-iri.update', $submission), $data)->assertNotFound();
        $this->assertNull($submission->fresh()->phil_iri['reviewed_at']);
    }

    public function test_invalid_teacher_observations_do_not_replace_saved_scores(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->submit($this->assessment($teacher, 'oral_reading', 0), User::factory()->create(), 0)->assertOk();
        $submission = AssessmentSubmission::firstOrFail();
        $original = $submission->phil_iri;
        foreach ([
            ['word_count' => 0, 'miscues' => 0], ['word_count' => 100, 'miscues' => -1],
            ['word_count' => 100, 'miscues' => 101], ['word_count' => 100, 'miscues' => 2.5],
            ['word_count' => 100, 'miscues' => 0, 'reading_seconds' => 0],
            ['word_count' => 100, 'miscues' => 0, 'comprehension_correct' => 0],
            ['word_count' => 100, 'miscues' => 0, 'comprehension_questions' => 5],
            ['word_count' => 100, 'miscues' => 0, 'comprehension_correct' => 6, 'comprehension_questions' => 5],
        ] as $data) {
            $this->actingAs($teacher)->patchJson(route('teacher.phil-iri.update', $submission), $data)->assertUnprocessable();
            $this->assertSame($original, $submission->fresh()->phil_iri);
        }
    }

    public function test_teacher_cannot_overwrite_comprehension_scored_by_the_server(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->submit($this->assessment($teacher, 'oral_reading', 5), User::factory()->create(), 3)->assertOk();
        $submission = AssessmentSubmission::firstOrFail();
        $this->actingAs($teacher)->patchJson(route('teacher.phil-iri.update', $submission), [
            'word_count' => 100, 'miscues' => 0, 'comprehension_correct' => 5, 'comprehension_questions' => 5,
        ])->assertUnprocessable();
        $this->patch(route('teacher.phil-iri.update', $submission), ['word_count' => 100, 'miscues' => 0])->assertRedirect();
        $this->assertSame('Instructional', $submission->fresh()->phil_iri['level']);
    }

    public function test_group_screening_uses_twenty_item_cutoff_not_reading_levels(): void
    {
        $teacher = User::factory()->teacher()->create();
        $assessment = $this->assessment($teacher, 'group_screening', 20);
        $this->submit($assessment, User::factory()->create(), 13)->assertOk()
            ->assertJsonPath('phil_iri.label', 'Further assessment needed')->assertJsonPath('phil_iri.level', null);
        $this->submit($assessment, User::factory()->create(), 14)->assertOk()
            ->assertJsonPath('phil_iri.label', 'At or above screening cutoff')->assertJsonPath('phil_iri.level', null);
        $this->submit($this->assessment($teacher, 'group_screening', 5), User::factory()->create(), 5)->assertOk()
            ->assertJsonPath('phil_iri.status', 'unsupported_screening')->assertJsonPath('phil_iri.level', null);
    }

    public function test_only_finished_silent_timer_is_used_for_reading_rate(): void
    {
        $teacher = User::factory()->teacher()->create();
        $assessment = $this->assessment($teacher);
        foreach (['paused', 'finished'] as $timer) {
            $response = $this->submit($assessment, User::factory()->create(), 4, [
                'revision' => 1, 'state' => ['answers' => [], 'phase' => 'finished', 'reading_seconds' => 120, 'timer_status' => $timer],
            ])->assertOk();
            $this->assertEquals($timer === 'finished' ? 50 : null, $response->json('phil_iri.words_per_minute'));
        }
    }

    public function test_saved_rubric_is_stable_across_content_changes_and_duplicate_submissions(): void
    {
        $assessment = $this->assessment(User::factory()->teacher()->create());
        $student = User::factory()->create();
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))->assertOk();
        $progress = \App\Models\AssessmentProgress::firstOrFail();
        $original = $this->submit($assessment, $student, 4, ['attempt_key' => $progress->attempt_key])->assertOk()->json('phil_iri');
        $assessment->update(['story_description' => 'Changed text', 'assessment_type' => 'listening_comprehension']);
        $repeated = $this->submit($assessment, $student, 0, ['attempt_key' => $progress->attempt_key])->assertOk()->json('phil_iri');
        $this->assertEquals($original, $repeated);
        $this->assertDatabaseCount('assessment_submissions', 1);
    }

    public function test_legacy_results_use_saved_counts_without_inventing_oral_observations(): void
    {
        $teacher = User::factory()->teacher()->create();
        foreach (['silent_reading', 'oral_reading'] as $type) {
            $assessment = $this->assessment($teacher, $type);
            $submission = new AssessmentSubmission(['correct_count' => 4, 'question_count' => 5]);
            $submission->setRelation('assessment', $assessment);
            $result = PhilIri::forSubmission($submission);
            $this->assertSame($type === 'oral_reading' ? 'awaiting_teacher' : 'complete', $result['status']);
            $this->assertNull($result['miscues']);
            $this->assertStringContainsString('strong understanding', $result['comprehension_interpretation']);
        }
    }

    public function test_existing_saved_results_get_interpretations_without_regrading_or_rewriting_them(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $assessment = $this->assessment($teacher);
        $response = $this->submit($assessment, $student, 3)->assertOk();
        $interpretation = $response->json('phil_iri.comprehension_interpretation');
        $this->assertStringContainsString('developing understanding', $interpretation);
        $submission = AssessmentSubmission::firstOrFail();
        $snapshot = $submission->phil_iri;
        unset($snapshot['comprehension_interpretation']);
        $submission->update(['phil_iri' => $snapshot]);
        $assessment->update(['manual_questions' => [], 'assessment_type' => 'group_screening']);

        $this->get(route('student.activities'))->assertOk()->assertSee($interpretation);
        $this->actingAs($teacher)->get(route('reports.student', $student))->assertOk()->assertSee($interpretation);
        $this->assertSame($interpretation, PhilIri::forSubmission($submission->fresh())['comprehension_interpretation']);
        $this->assertEquals($snapshot, $submission->fresh()->phil_iri);
        $this->assertEquals(750, $submission->fresh()->points);
    }

    public function test_teacher_revisions_refresh_the_comprehension_interpretation(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $this->submit($this->assessment($teacher, 'oral_reading', 0), $student, 0)->assertOk()
            ->assertJsonPath('phil_iri.comprehension_interpretation', fn ($text) => str_contains($text, 'has not been assessed yet'));
        $submission = AssessmentSubmission::firstOrFail();
        foreach ([4 => 'strong understanding', 3 => 'developing understanding', 2 => 'currently challenging'] as $correct => $expected) {
            $this->actingAs($teacher)->patch(route('teacher.phil-iri.update', $submission), [
                'word_count' => 100, 'miscues' => 0,
                'comprehension_correct' => $correct, 'comprehension_questions' => 5,
            ])->assertRedirect();
            $this->assertStringContainsString($expected, $submission->fresh()->phil_iri['comprehension_interpretation']);
            $this->get(route('teacher.phil-iri.show', $submission))->assertOk()->assertSee($expected);
            $this->actingAs($student)->get(route('student.activities'))->assertOk()->assertSee($expected);
        }
    }
}
