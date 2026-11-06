<?php

namespace App\Console\Commands;

use App\Support\NumeracyAttemptScorer;
use Illuminate\Console\Command;

class BackfillNumeracyScores extends Command
{
    protected $signature = 'numeracy:backfill-scores';

    protected $description = 'Automatically score previously submitted numeracy game attempts that contain structured answers';

    public function handle(NumeracyAttemptScorer $scorer): int
    {
        $summary = $scorer->backfill();

        $this->table(
            ['Checked', 'Automatically scored', 'Still needs teacher review', 'Failed'],
            [[$summary['checked'], $summary['scored'], $summary['manual'], $summary['failed']]]
        );

        return $summary['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
