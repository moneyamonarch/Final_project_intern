<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneFeedback extends Command
{
    protected $signature = 'recommender:prune {--days=180}';

    protected $description = 'Delete runs and feedback older than N days (privacy retention)';

    public function handle(): int
    {
        $cut = now()->subDays((int) $this->option('days'));
        $n = DB::table('recommendation_runs')->where('created_at', '<', $cut)->delete(); // feedback cascades
        $this->info("Deleted $n runs older than {$this->option('days')} days.");

        return self::SUCCESS;
    }
}
