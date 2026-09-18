<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_progress', fn (Blueprint $table) => $table->timestamp('multiplication_table_unlocked_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('assessment_progress', fn (Blueprint $table) => $table->dropColumn('multiplication_table_unlocked_at'));
    }
};
