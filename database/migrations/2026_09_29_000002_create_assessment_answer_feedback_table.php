<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answer_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('question_index');
            $table->char('source_hash', 64);
            $table->text('answer');
            $table->timestamps();
            $table->unique(['assessment_submission_id', 'question_index'], 'answer_feedback_submission_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answer_feedback');
    }
};
