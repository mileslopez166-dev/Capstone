<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('student_name')->nullable()->after('target_section');
            $table->string('grade_level')->default('6')->after('student_name');
            $table->string('assessment_type')->default('silent_reading')->after('grade_level');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['student_name', 'grade_level', 'assessment_type']);
        });
    }
};
