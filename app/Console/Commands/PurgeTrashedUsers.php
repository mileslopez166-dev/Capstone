<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PurgeTrashedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:purge-trashed {--days=30 : Number of days to keep trashed users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete trashed users older than the configured number of days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $threshold = Carbon::now()->subDays($days);

        $users = User::onlyTrashed()->where('deleted_at', '<=', $threshold)->get();

        $this->info('Found '.$users->count().' users to purge.');

        foreach ($users as $user) {
            $this->line('Permanently deleting: '.$user->email.' (id: '.$user->id.')');
            $user->forceDelete();
        }

        $this->info('Purge complete.');

        return 0;
    }
}
