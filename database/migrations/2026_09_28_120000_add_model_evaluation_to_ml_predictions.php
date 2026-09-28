<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ml_predictions', function (Blueprint $table) {
            $table->json('model_evaluation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ml_predictions', function (Blueprint $table) {
            $table->dropColumn('model_evaluation');
        });
    }
};
