<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_progress', function (Blueprint $table) {
            $table->foreignId('administered_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_progress', function (Blueprint $table) {
            $table->dropConstrainedForeignId('administered_by');
        });
    }
};
