<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->unique()->constrained('assessment_submissions')->nullOnDelete();
            $table->string('title');
            $table->string('source_title');
            $table->text('story')->nullable();
            $table->text('instructions')->nullable();
            $table->json('questions');
            $table->json('words');
            $table->json('progress')->nullable();
            $table->string('status', 20)->default('assigned');
            $table->unsignedSmallInteger('reward_coins')->default(25);
            $table->timestamp('completed_at')->nullable();
            $table->text('feedback')->nullable();
            $table->json('word_reviews')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status']);
            $table->index(['teacher_id', 'status']);
        });

        Schema::create('practice_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_mission_id')->constrained()->cascadeOnDelete();
            $table->uuid('attempt_key')->unique();
            $table->json('answers');
            $table->json('practiced_words');
            $table->unsignedSmallInteger('correct_count');
            $table->unsignedSmallInteger('question_count');
            $table->timestamps();
        });

        Schema::create('practice_coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_mission_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('item_key', 80)->nullable();
            $table->integer('amount');
            $table->string('description');
            $table->timestamps();
            $table->unique(['user_id', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_coin_transactions');
        Schema::dropIfExists('practice_attempts');
        Schema::dropIfExists('practice_missions');
    }
};
