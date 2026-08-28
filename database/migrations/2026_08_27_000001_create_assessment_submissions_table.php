<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('question_count')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('possible_points')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_submissions');
    }
};
