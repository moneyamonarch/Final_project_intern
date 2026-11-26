<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Synthetic feedback on existing runs (open each persona first). Flagged is_synthetic; never real usage. */
class DemoFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DB::table('recommendation_runs')->get() as $run) {
            foreach (json_decode($run->results, true) as $r) {
                for ($i = 0; $i < 6; $i++) {
                    $event = $r['rank'] <= 3 ? 'thumbs_up' : ($r['rank'] >= 8 ? 'thumbs_down' : 'impression');
                    DB::table('feedback_events')->insert(['run_id' => $run->id, 'portfolio_id' => $r['portfolio_id'], 'event' => $event,
                        'rank' => $r['rank'], 'is_synthetic' => true, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }
}
