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
