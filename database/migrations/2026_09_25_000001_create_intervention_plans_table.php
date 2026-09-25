<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->text('notes');
            $table->date('follow_up_date')->nullable();
            $table->string('status', 20)->default('planned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervention_plans');
    }
};
