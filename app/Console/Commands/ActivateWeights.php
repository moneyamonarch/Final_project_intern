<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActivateWeights extends Command
{
    protected $signature = 'recommender:activate-weights {version : Version number, or 0 for built-in defaults}';

    protected $description = 'Activate a stored weight version (rollback)';

    public function handle(): int
    {
        $v = (int) $this->argument('version');
        if ($v !== 0 && ! DB::table('recommender_weights')->where('version', $v)->exists()) {
            $this->error("Version $v not found.");

            return self::FAILURE;
        }
        DB::table('recommender_weights')->update(['is_active' => false]);
        if ($v !== 0) {
            DB::table('recommender_weights')->where('version', $v)->update(['is_active' => true]);
        }
        $this->info($v === 0 ? 'Default weights active.' : "Weights v$v active.");

        return self::SUCCESS;
    }
}
