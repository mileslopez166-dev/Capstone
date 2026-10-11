<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedSmallInteger('question_time_limit_seconds')->default(120)->after('retry_limit');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn('question_time_limit_seconds'));
    }
};
