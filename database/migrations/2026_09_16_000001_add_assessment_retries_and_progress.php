<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedSmallInteger('retry_limit')->default(0);
        });

        Schema::create('assessment_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->uuid('attempt_key')->unique();
            $table->unsignedBigInteger('revision')->default(0);
            $table->json('state')->nullable();
            $table->foreignId('submission_id')->nullable()->constrained('assessment_submissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['assessment_id', 'user_id', 'attempt_number'], 'assessment_progress_attempt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_progress');
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn('retry_limit'));
    }
};
