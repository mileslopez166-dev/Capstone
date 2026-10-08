<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->rescale(50);
    }

    public function down(): void
    {
        $this->rescale(250);
    }

    private function rescale(int $pointsPerCorrectAnswer): void
    {
        DB::table('assessment_submissions')
            ->select(['id', 'correct_count', 'question_count'])
            ->orderBy('id')
            ->chunkById(200, function ($submissions) use ($pointsPerCorrectAnswer): void {
                foreach ($submissions as $submission) {
                    DB::table('assessment_submissions')
                        ->where('id', $submission->id)
                        ->update([
                            'points' => (int) $submission->correct_count * $pointsPerCorrectAnswer,
                            'possible_points' => (int) $submission->question_count * $pointsPerCorrectAnswer,
                        ]);
                }
            });
    }
};
