<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedInteger('expiration_value')->nullable()->after('question_time_limit_seconds');
            $table->string('expiration_unit', 10)->nullable()->after('expiration_value');
            $table->timestamp('expires_at')->nullable()->after('expiration_unit')->index();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['expiration_value', 'expiration_unit', 'expires_at']);
        });
    }
};
