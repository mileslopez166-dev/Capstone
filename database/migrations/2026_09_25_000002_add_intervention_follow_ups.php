<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intervention_plans', function (Blueprint $table) {
            $table->foreignId('follow_up_assessment_id')->nullable()->constrained('assessments')->nullOnDelete();
            $table->foreignId('follow_up_submission_id')->nullable()->constrained('assessment_submissions')->nullOnDelete();
            $table->timestamp('follow_up_linked_at')->nullable();
            $table->text('comparison_basis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('intervention_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('follow_up_submission_id');
            $table->dropConstrainedForeignId('follow_up_assessment_id');
            $table->dropColumn(['follow_up_linked_at', 'comparison_basis']);
        });
    }
};
