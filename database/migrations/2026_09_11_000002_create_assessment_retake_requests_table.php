<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_retake_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('requested_tries')->default(1);
            $table->unsignedInteger('approved_tries')->default(0);
            $table->unsignedInteger('remaining_tries')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['assessment_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_retake_requests');
    }
};