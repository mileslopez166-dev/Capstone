<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', fn (Blueprint $table) => $table->string('question_selection')->default('fixed'));
        foreach (['assessment_progress', 'assessment_submissions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->json('question_snapshot')->nullable();
                $table->json('selection_context')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['assessment_progress', 'assessment_submissions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['question_snapshot', 'selection_context']));
        }
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn('question_selection'));
    }
};
