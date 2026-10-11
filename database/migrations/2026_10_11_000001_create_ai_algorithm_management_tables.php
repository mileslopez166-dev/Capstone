<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_model_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 20);
            $table->string('status', 30)->default('queued');
            $table->json('dataset_info')->nullable();
            $table->json('comparison_metrics')->nullable();
            $table->string('recommended_algorithm', 40)->nullable();
            $table->json('final_test_metrics')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['subject', 'created_at']);
        });

        Schema::create('ai_model_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('ai_model_evaluations')->cascadeOnDelete();
            $table->string('subject', 20);
            $table->string('algorithm', 40);
            $table->string('model_name');
            $table->string('version', 80);
            $table->string('status', 30);
            $table->string('artifact_path')->nullable();
            $table->json('metrics')->nullable();
            $table->json('provenance')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique(['subject', 'model_name', 'version'], 'ai_model_version_unique');
            $table->index(['subject', 'status']);
        });

        Schema::create('ai_recommendation_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 20)->default('all');
            $table->string('approach', 40);
            $table->string('status', 30);
            $table->json('metrics')->nullable();
            $table->json('dataset_info')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
            $table->index(['subject', 'approach', 'created_at'], 'ai_rec_eval_lookup');
        });

        Schema::create('ai_active_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('configuration_type', 30);
            $table->string('subject', 20)->default('all');
            $table->string('active_key');
            $table->string('previous_key')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at');
            $table->timestamps();
            $table->unique(['configuration_type', 'subject'], 'ai_active_configuration_unique');
        });

        Schema::create('ai_model_activation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('configuration_type', 30);
            $table->string('subject', 20)->default('all');
            $table->string('previous_key')->nullable();
            $table->string('new_key');
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('activated');
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['configuration_type', 'subject', 'created_at'], 'ai_activation_lookup');
        });

        Schema::create('learning_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('source_submission_id')->nullable()->constrained('assessment_submissions')->nullOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('approach', 40);
            $table->decimal('score', 7, 6);
            $table->json('reasons')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['student_id', 'source_submission_id', 'assessment_id'], 'learning_recommendation_unique');
            $table->index(['student_id', 'status', 'score'], 'learning_recommendation_lookup');
        });

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_recommendations');
        Schema::dropIfExists('ai_model_activation_logs');
        Schema::dropIfExists('ai_active_configurations');
        Schema::dropIfExists('ai_recommendation_evaluations');
        Schema::dropIfExists('ai_model_versions');
        Schema::dropIfExists('ai_model_evaluations');
    }
};
