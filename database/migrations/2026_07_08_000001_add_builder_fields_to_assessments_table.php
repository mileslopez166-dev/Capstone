<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('quiz_type')->default('multiple_choice')->after('subject');
            $table->string('delivery_method')->default('upload')->after('quiz_type');
            $table->string('target_section')->default('all')->after('delivery_method');
            $table->json('focus_areas')->nullable()->after('target_section');
            $table->string('asset_path')->nullable()->after('focus_areas');
            $table->json('manual_questions')->nullable()->after('asset_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn([
                'quiz_type',
                'delivery_method',
                'target_section',
                'focus_areas',
                'asset_path',
                'manual_questions',
            ]);
        });
    }
};
