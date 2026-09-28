<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\MLPrediction;
use App\Models\User;
use App\Support\MLPredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MLPredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ml.enabled' => true,
            'ml.endpoint' => 'http://127.0.0.1:8001',
            'ml.model_name' => 'reading_level_random_forest_v1',
        ]);
        Http::swap(new Factory());
        Http::preventStrayRequests();
    }

    public function test_prediction_service_sends_anonymous_features_and_saves_result(): void
    {
        Http::fake([
            '127.0.0.1:8001/predict' => Http::response([
                'prediction' => 'Instructional',
                'confidence' => 0.89,
                'model_name' => 'reading_level_random_forest_v1',
            ]),
        ]);

        $student = User::factory()->create(['name' => 'Private Student', 'email' => 'private@example.test']);
        $submission = $this->submission($student);

        $prediction = app(MLPredictionService::class)->predictForSubmission($submission);

        $this->assertInstanceOf(MLPrediction::class, $prediction);
        $this->assertDatabaseHas('ml_predictions', [
            'assessment_submission_id' => $submission->id,
            'student_id' => $student->id,
            'prediction' => 'Instructional',
            'recommendation' => 'Provide guided practice and monitor progress',
        ]);

        Http::assertSent(function ($request) use ($student) {
            $this->assertStringNotContainsString($student->name, $request->body());
            $this->assertStringNotContainsString($student->email, $request->body());
            $this->assertArrayHasKey('comprehension_score', $request->data());
            $this->assertArrayHasKey('assessment_attempts', $request->data());

            return true;
        });
    }

    public function test_prediction_service_is_silent_when_disabled(): void
    {
        config(['ml.enabled' => false]);

        $this->assertNull(app(MLPredictionService::class)->predictForSubmission($this->submission(User::factory()->create())));
        Http::assertNothingSent();
        $this->assertDatabaseCount('ml_predictions', 0);
    }

    public function test_teacher_dashboard_displays_saved_ai_learning_insights(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $submission = $this->submission($student, $teacher);
        MLPrediction::create([
            'assessment_submission_id' => $submission->id,
            'student_id' => $student->id,
            'model_name' => 'reading_level_random_forest_v1',
            'prediction' => 'Instructional',
            'confidence_score' => 0.89,
            'input_data' => ['comprehension_score' => 75],
            'recommendation' => 'Provide reading comprehension activities',
        ]);

        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSeeText('AI Learning Insights')
            ->assertSeeText('Student_'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT))
            ->assertSeeText('ML Prediction: Instructional')
            ->assertSeeText('Confidence: 89%')
            ->assertSeeText('AI prediction is a support tool');
    }

    public function test_student_sees_saved_prediction_confidence_and_test_accuracy_separately(): void
    {
        Http::fake(['*' => Http::response([
            'prediction' => 'Instructional', 'confidence' => 0.89,
            'evaluation' => ['method' => 'held_out_test', 'accuracy' => 0.925, 'test_rows' => 80],
        ])]);
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $prediction = app(MLPredictionService::class)->predictForSubmission($submission);
        $this->assertSame(0.925, $prediction->fresh()->model_evaluation['accuracy']);

        $this->actingAs($student)->get(route('student.activities'))
            ->assertOk()->assertSeeText('100%')->assertSeeText('ML learning prediction')
            ->assertSeeText('Instructional')->assertSeeText('Prediction confidence')
            ->assertSeeText('89%')->assertSeeText('Model test accuracy')
            ->assertSeeText('92.5%')->assertSeeText('Held-out test: 80 records.');

        $this->actingAs(User::factory()->create())->get(route('student.activities'))
            ->assertOk()->assertDontSeeText('Held-out test: 80 records.');
    }

    public function test_legacy_prediction_does_not_invent_test_accuracy_or_confidence(): void
    {
        Http::fake(['*' => Http::response(['prediction' => 'Instructional'])]);
        $student = User::factory()->create();
        $submission = $this->submission($student);
        $prediction = app(MLPredictionService::class)->predictForSubmission($submission);
        $this->assertNull($prediction->model_evaluation);
        $this->assertNull($prediction->confidence_score);

        $this->actingAs($student)->get(route('student.activities'))
            ->assertOk()->assertSeeText('Not available')
            ->assertSeeText('No held-out test result was saved with this prediction.');
    }

    public function test_missing_prediction_does_not_hide_the_saved_score(): void
    {
        $student = User::factory()->create();
        $this->submission($student);
        $this->actingAs($student)->get(route('student.activities'))
            ->assertOk()->assertSeeText('100%')
            ->assertSeeText('No ML prediction was saved for this attempt.');
        Http::assertNothingSent();
    }

    public function test_invalid_or_training_set_evaluations_are_not_saved_as_test_accuracy(): void
    {
        $submission = $this->submission(User::factory()->create());
        foreach ([
            ['method' => 'full_dataset', 'accuracy' => 0.9998, 'test_rows' => 44497],
            ['method' => 'held_out_test', 'accuracy' => 99, 'test_rows' => 100],
            ['method' => 'held_out_test', 'accuracy' => null, 'test_rows' => 100],
            ['method' => 'held_out_test', 'accuracy' => 0.8, 'test_rows' => 0],
        ] as $evaluation) {
            Http::swap(new Factory());
            Http::fake(['*' => Http::response(['prediction' => 'Independent', 'evaluation' => $evaluation])]);
            $this->assertNull(app(MLPredictionService::class)->predictForSubmission($submission)->model_evaluation);
        }
    }

    public function test_live_submission_returns_model_metrics_without_changing_the_score(): void
    {
        Http::fake(['*' => Http::response([
            'prediction' => 'Instructional', 'confidence' => 0.89,
            'evaluation' => ['method' => 'held_out_test', 'accuracy' => 0.925, 'test_rows' => 80],
        ])]);
        $student = User::factory()->create();
        $assessment = $this->submission(User::factory()->create())->assessment;
        $this->actingAs($student)->get(route('student.assessments.show', $assessment))
            ->assertOk()->assertSee('data-ml-live', false);
        $this->postJson(route('student.assessments.submit', $assessment), ['answers' => ['A']])
            ->assertOk()->assertJsonPath('accuracy', 100)->assertJsonPath('points', 250)
            ->assertJsonPath('ml_prediction.confidence', 0.89)
            ->assertJsonPath('ml_prediction.evaluation.accuracy', 0.925);
    }

    public function test_score_saves_when_ml_is_unavailable(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $assessment = $this->submission(User::factory()->create())->assessment;
        $this->actingAs(User::factory()->create())
            ->postJson(route('student.assessments.submit', $assessment), ['answers' => ['A']])
            ->assertOk()->assertJsonPath('accuracy', 100)->assertJsonPath('ml_prediction', null);
    }

    public function test_teacher_overall_prediction_aggregates_latest_scored_attempts_and_is_cached(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        Http::fake(['*' => Http::response(['prediction' => 'Instructional', 'confidence' => 0.84])]);
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $first = $this->submission($student, $teacher);
        $second = $this->submission($student, $teacher);
        $this->submission($student); // Another teacher's assessment must not contribute.
        $retake = $first->replicate();
        $retake->fill([
            'attempt_number' => 2, 'correct_count' => 0, 'points' => 0,
            'phil_iri' => \App\Support\PhilIri::initial($first->assessment, 0, 1),
            'submitted_at' => now()->addMinute(),
        ])->save();

        $url = route('students.ml-level', $student);
        $this->actingAs($teacher)->getJson($url)->assertOk()
            ->assertJsonPath('status', 'ready')->assertJsonPath('assessment_count', 2)
            ->assertJsonPath('overall_percentage', 50)
            ->assertJsonPath('prediction.prediction', 'Instructional')
            ->assertJsonPath('prediction.confidence', 0.84);
        Http::assertSent(fn ($request) => $request['reading_score'] == 50
            && $request['comprehension_score'] == 50 && $request['reading_accuracy'] == 50
            && $request['numeracy_score'] === null && $request['assessment_attempts'] == 1.5
            && ! str_contains($request->body(), $student->email));
        $this->getJson($url)->assertOk();
        Http::assertSentCount(1);

        $second->update(['correct_count' => 0, 'phil_iri' => \App\Support\PhilIri::initial($second->assessment, 0, 1)]);
        $this->getJson($url)->assertOk()->assertJsonPath('overall_percentage', 0);
        Http::assertSentCount(2);
        $this->assertDatabaseCount('ml_predictions', 0); // Overall estimates never replace attempt predictions.
    }

    public function test_overall_prediction_keeps_missing_subjects_null_and_real_zero_scores(): void
    {
        Http::fake(['*' => Http::response(['prediction' => 'Frustration', 'confidence' => 0.8])]);
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $this->submission($student, $teacher);
        $math = $this->submission($student, $teacher);
        $math->assessment->update(['subject' => 'numeracy']);
        $math->update(['correct_count' => 0, 'phil_iri' => null]);
        $this->actingAs($teacher)->getJson(route('students.ml-level', $student))->assertOk()
            ->assertJsonPath('overall_percentage', 50)->assertJsonPath('assessment_count', 2);
        Http::assertSent(fn ($request) => $request['reading_score'] == 100
            && $request['numeracy_score'] == 0 && $request['listening_score'] === null);
    }

    public function test_overall_estimate_is_teacher_only_and_does_not_leak_another_teachers_results(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $this->submission($student, $teacher);
        $url = route('students.ml-level', $student);
        $this->actingAs($student)->getJson($url)->assertForbidden();
        $this->get(route('students.index'))->assertForbidden();
        $this->get(route('students.show', $student))->assertForbidden();
        $this->actingAs($otherTeacher)->getJson($url)->assertOk()
            ->assertJsonPath('status', 'no_data')->assertJsonPath('assessment_count', 0)
            ->assertJsonPath('overall_percentage', null)->assertJsonPath('prediction', null);
        $this->actingAs($teacher)->getJson(route('students.ml-level', $otherTeacher))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_teacher_pages_show_overall_estimate_without_blocking_render_on_ml(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $this->submission($student, $teacher);
        $this->actingAs($teacher)->get(route('students.index'))->assertOk()
            ->assertSeeText('Overall ML Estimate')->assertSee('data-teacher-ml-level', false);
        $this->get(route('students.show', $student))->assertOk()->assertSeeText('Overall ML Estimate');
        $this->actingAs($student)->get(route('student.activities'))->assertOk()
            ->assertDontSee('data-teacher-ml-level', false);
        Http::assertNothingSent();
    }

    public function test_overall_estimate_handles_disabled_unavailable_and_invalid_model_without_inventing_a_level(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $this->submission($student, $teacher);
        $url = route('students.ml-level', $student);
        config(['ml.enabled' => false]);
        $this->actingAs($teacher)->getJson($url)->assertOk()->assertJsonPath('status', 'disabled')
            ->assertJsonPath('prediction', null)->assertJsonPath('overall_percentage', 100);
        Http::assertNothingSent();
        config(['ml.enabled' => true]);
        foreach ([Http::response([], 503), Http::response(['prediction' => 'Unknown'])] as $response) {
            \Illuminate\Support\Facades\Cache::flush();
            Http::swap(new Factory());
            Http::fake(['*' => $response]);
            $this->getJson($url)->assertOk()->assertJsonPath('status', 'unavailable')
                ->assertJsonPath('prediction', null)->assertJsonPath('overall_percentage', 100);
        }
    }

    private function submission(User $student, ?User $teacher = null): AssessmentSubmission
    {
        $assessment = Assessment::create([
            'created_by' => ($teacher ?? User::factory()->teacher()->create())->id,
            'title' => 'Reading garden',
            'subject' => 'literacy',
            'quiz_type' => 'multiple_choice',
            'delivery_method' => 'manual',
            'target_section' => 'all',
            'assessment_type' => 'silent_reading',
            'focus_areas' => [],
            'story_title' => 'Garden',
            'story_description' => 'A class made a garden.',
            'manual_questions' => [
                ['question' => 'What did they make?', 'answers' => ['A' => 'Garden'], 'correct_answer' => 'A'],
            ],
            'status' => 'published',
        ]);

        return AssessmentSubmission::create([
            'assessment_id' => $assessment->id,
            'user_id' => $student->id,
            'attempt_number' => 1,
            'answers' => ['A'],
            'correct_count' => 1,
            'question_count' => 1,
            'points' => 250,
            'possible_points' => 250,
            'phil_iri' => \App\Support\PhilIri::initial($assessment, 1, 1),
            'submitted_at' => now(),
        ]);
    }
}
