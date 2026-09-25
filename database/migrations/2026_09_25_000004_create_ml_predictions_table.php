<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_submission_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('model_name');
            $table->string('prediction', 30);
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->json('input_data');
            $table->string('recommendation')->nullable();
            $table->timestamps();

            $table->unique(['assessment_submission_id', 'model_name']);
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
    }
};
