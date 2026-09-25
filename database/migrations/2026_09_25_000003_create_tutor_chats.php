<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 20);
            $table->timestamp('teacher_help_requested_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('tutor_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_chat_id')->constrained()->cascadeOnDelete();
            $table->uuid('request_id')->unique();
            $table->text('question');
            $table->text('answer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_turns');
        Schema::dropIfExists('tutor_chats');
    }
};
