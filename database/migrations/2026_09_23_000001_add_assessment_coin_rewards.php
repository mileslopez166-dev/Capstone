<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_coin_transactions', function (Blueprint $table) {
            $table->foreignId('assessment_submission_id')->nullable()->unique()
                ->constrained('assessment_submissions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('practice_coin_transactions', function (Blueprint $table) {
            $table->dropForeign(['assessment_submission_id']);
            $table->dropUnique(['assessment_submission_id']);
            $table->dropColumn('assessment_submission_id');
        });
    }
};
