<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedSmallInteger('worksheet_number')->nullable();
            $table->unsignedSmallInteger('worksheet_total')->nullable();
        });
        Schema::create('worksheet_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('progress_id')->unique()->constrained('assessment_progress')->cascadeOnDelete();
            $table->json('pages');
            $table->unsignedSmallInteger('total');
            $table->unsignedSmallInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['assessment_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worksheet_attempts');
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn(['worksheet_number', 'worksheet_total']));
    }
};
