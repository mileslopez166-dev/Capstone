<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_submissions', function (Blueprint $table) {
            $table->index('assessment_id', 'assessment_submissions_assessment_id_index');
        });

        Schema::table('assessment_submissions', function (Blueprint $table) {
            $table->dropUnique(['assessment_id', 'user_id']);
            $table->unsignedInteger('attempt_number')->default(1)->after('user_id');
            $table->index(['assessment_id', 'user_id', 'attempt_number'], 'assessment_submissions_attempt_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_submissions', function (Blueprint $table) {
            $table->dropIndex('assessment_submissions_attempt_lookup_index');
            $table->dropColumn('attempt_number');
            $table->unique(['assessment_id', 'user_id']);
        });

        Schema::table('assessment_submissions', function (Blueprint $table) {
            $table->dropIndex('assessment_submissions_assessment_id_index');
        });
    }
};