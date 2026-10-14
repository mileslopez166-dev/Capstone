<?php

namespace Tests\Feature;

use App\Jobs\EvaluateClassificationModels;
use App\Jobs\EvaluateRecommendations;
use App\Models\AiModelEvaluation;
use App\Models\AiModelVersion;
use App\Models\AiRecommendationEvaluation;
use App\Models\Assessment;
use App\Models\AssessmentSubmission;
use App\Models\User;
use App\Support\LearningRecommender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiAlgorithmManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_model_evaluation_and_queue_jobs(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->get(route('admin.ai-models.index'))->assertForbidden();

        Queue::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.ai-models.index'))
            ->assertOk()->assertSeeText('AI Model Evaluation')
            ->assertSeeText('Recent learning recommendations');
        $this->post(route('admin.ai-models.classification.evaluate'), ['subject' => 'literacy'])
            ->assertRedirect();
        $this->post(route('admin.ai-models.recommendations.evaluate'))->assertRedirect();

        Queue::assertPushed(EvaluateClassificationModels::class);
        Queue::assertPushed(EvaluateRecommendations::class);
        $this->assertDatabaseHas('ai_model_evaluations', ['subject' => 'literacy', 'status' => 'queued']);
    }

    public function test_admin_sees_a_separate_evidence_table_for_each_algorithm(): void
    {
        $admin = User::factory()->admin()->create();
        $evaluation = AiModelEvaluation::create([
            'requested_by' => $admin->id,
            'subject' => 'literacy',
            'status' => 'evaluated',
            'dataset_info' => [
                'rows' => 48,
                'learners' => 12,
                'development_rows' => 38,
                'held_out_rows' => 10,
                'class_distribution' => ['Frustration' => 16, 'Instructional' => 18, 'Independent' => 14],
                'features' => ['reading_score', 'comprehension_score'],
            ],
            'recommended_algorithm' => 'decision_tree',
            'completed_at' => now(),
        ]);
        AiModelVersion::create([
            'evaluation_id' => $evaluation->id,
            'subject' => 'literacy',
            'algorithm' => 'decision_tree',
            'model_name' => 'literacy_decision_tree_defense',
            'version' => 'defense-v1',
            'status' => 'validated',
            'metrics' => [
                'accuracy' => .81,
                'precision_macro' => .80,
                'recall_macro' => .79,
                'f1_macro' => .795,
                'folds' => 5,
                'standard_deviation_f1' => .03,
                'best_parameters' => ['classifier__max_depth' => 6],
            ],
            'provenance' => [
                'dataset' => $evaluation->dataset_info,
                'protocol' => ['model_selection' => '5-fold stratified learner-grouped cross-validation'],
                'random_seed' => 42,
            ],
            'validated_at' => now(),
        ]);
        AiRecommendationEvaluation::create([
            'requested_by' => $admin->id,
            'subject' => 'all',
            'approach' => 'content_based',
            'status' => 'evaluated',
            'metrics' => ['precision_at_5' => .20, 'recall_at_5' => .60, 'ndcg_at_5' => .52, 'coverage' => .75],
            'dataset_info' => ['interactions' => 80, 'learners' => 12, 'activities' => 9, 'holdouts' => 8],
            'evaluated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai-models.index'))->assertOk();

        $response->assertSeeText('Classification algorithm evidence')
            ->assertSeeText('Recommendation algorithm evidence')
            ->assertSeeText('Selected parameters')
            ->assertSeeText('Max Depth: 6')
            ->assertSeeText('79.5%')
            ->assertSeeText('Cosine similarity');
        $this->assertStringContainsString('aria-label="Decision Tree literacy evidence table"', $response->getContent());
        $this->assertStringContainsString('aria-label="Random Forest literacy evidence table"', $response->getContent());
        $this->assertStringContainsString('aria-label="Support Vector Machine literacy evidence table"', $response->getContent());
        $this->assertStringContainsString('aria-label="Content-Based Filtering evidence table"', $response->getContent());
        $this->assertStringContainsString('aria-label="Collaborative Filtering evidence table"', $response->getContent());
        $this->assertStringContainsString('aria-label="Hybrid Recommendation evidence table"', $response->getContent());
    }

    public function test_validated_exact_model_can_be_activated_without_changing_scores(): void
    {
        Http::fake(['*/admin/activate' => Http::response([
            'status' => 'activated', 'model_name' => 'literacy_svm_20261011T120000Z',
        ])]);
        config(['ai_models.endpoint' => 'http://ml-api:8001', 'ai_models.admin_token' => 'secret']);
        $admin = User::factory()->admin()->create();
        $evaluation = AiModelEvaluation::create([
            'requested_by' => $admin->id, 'subject' => 'literacy', 'status' => 'evaluated',
        ]);
        $version = AiModelVersion::create([
            'evaluation_id' => $evaluation->id, 'subject' => 'literacy', 'algorithm' => 'svm',
            'model_name' => 'literacy_svm_20261011T120000Z', 'version' => '20261011T120000Z',
            'status' => 'validated', 'validated_at' => now(),
        ]);
        $submission = $this->submission(User::factory()->create(), $this->assessment(User::factory()->teacher()->create()), 1, 1);

        $this->actingAs($admin)->post(route('admin.ai-models.classification.activate', $version))->assertRedirect();

        Http::assertSent(fn ($request) => $request['model_name'] === $version->model_name
            && $request['version'] === $version->version && $request['algorithm'] === 'svm');
        $this->assertDatabaseHas('ai_active_configurations', [
            'configuration_type' => 'classification', 'subject' => 'literacy', 'active_key' => $version->model_name,
        ]);
        $this->assertSame(1, $submission->fresh()->correct_count);
        $this->assertSame(50, $submission->fresh()->points);
    }

    public function test_unvalidated_model_cannot_be_activated(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->admin()->create();
        $evaluation = AiModelEvaluation::create([
            'requested_by' => $admin->id, 'subject' => 'literacy', 'status' => 'training',
        ]);
        $version = AiModelVersion::create([
            'evaluation_id' => $evaluation->id, 'subject' => 'literacy', 'algorithm' => 'decision_tree',
            'model_name' => 'unfinished', 'version' => 'pending', 'status' => 'training',
        ]);

        $this->actingAs($admin)->from(route('admin.ai-models.index'))
            ->post(route('admin.ai-models.classification.activate', $version))
            ->assertSessionHasErrors('model');
        $this->assertDatabaseCount('ai_active_configurations', 0);
    }

    public function test_content_recommendations_and_hybrid_cold_start_use_real_eligible_activities(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['section' => 'Magalang']);
        $sourceAssessment = $this->assessment($teacher, ['title' => 'Source Reading', 'focus_areas' => ['Inference']]);
        $target = $this->assessment($teacher, ['title' => 'Inference Practice', 'focus_areas' => ['Inference']]);
        $this->assessment($teacher, ['title' => 'Other Section', 'target_section' => 'section_b']);
        $source = $this->submission($student, $sourceAssessment, 1, 1);

        $ranked = app(LearningRecommender::class)->recommend($student, $source, 'hybrid', true);

        $this->assertTrue($ranked['fallback']);
        $this->assertSame('content_based', $ranked['approach']);
        $this->assertSame([$target->id], $ranked['items']->pluck('assessment.id')->all());
        $this->assertDatabaseHas('learning_recommendations', [
            'student_id' => $student->id, 'assessment_id' => $target->id,
            'source_submission_id' => $source->id, 'approach' => 'content_based', 'status' => 'active',
        ]);
        $this->actingAs($student)->get(route('student.activities', ['subject' => 'literacy']))
            ->assertOk()->assertSeeText('Recommended next')->assertSeeText('Inference Practice');
        $target->update(['status' => 'draft']);
        $this->get(route('student.activities', ['subject' => 'literacy']))
            ->assertOk()->assertDontSeeText('Recommended next')->assertDontSeeText('Inference Practice');
        $this->assertSame(50, $source->fresh()->points);
    }

    public function test_sparse_recommendation_evaluation_reports_insufficient_data(): void
    {
        $admin = User::factory()->admin()->create();
        app(LearningRecommender::class)->evaluate($admin->id);

        $this->assertDatabaseCount('ai_recommendation_evaluations', 3);
        $this->assertSame(
            ['insufficient_data'],
            AiRecommendationEvaluation::pluck('status')->unique()->values()->all(),
        );
    }

    public function test_only_evaluated_recommendation_approach_can_be_activated(): void
    {
        $admin = User::factory()->admin()->create();
        AiRecommendationEvaluation::create([
            'requested_by' => $admin->id, 'subject' => 'all', 'approach' => 'collaborative',
            'status' => 'insufficient_data', 'evaluated_at' => now(),
        ]);

        $this->actingAs($admin)->from(route('admin.ai-models.index'))
            ->post(route('admin.ai-models.recommendations.activate'), ['approach' => 'collaborative'])
            ->assertSessionHasErrors('recommendation');
        $this->assertDatabaseCount('ai_active_configurations', 0);
    }

    private function assessment(User $teacher, array $attributes = []): Assessment
    {
        return Assessment::create(array_merge([
            'created_by' => $teacher->id, 'title' => 'Reading Activity', 'subject' => 'literacy',
            'status' => 'published', 'target_section' => 'section_a', 'assessment_type' => 'silent_reading',
            'focus_areas' => ['Comprehension'], 'manual_questions' => [],
        ], $attributes));
    }

    private function submission(User $student, Assessment $assessment, int $correct, int $questions): AssessmentSubmission
    {
        return AssessmentSubmission::create([
            'assessment_id' => $assessment->id, 'user_id' => $student->id, 'attempt_number' => 1,
            'answers' => [], 'correct_count' => $correct, 'question_count' => $questions,
            'points' => $correct * 50, 'possible_points' => $questions * 50,
            'submitted_at' => now(),
        ]);
    }
}
