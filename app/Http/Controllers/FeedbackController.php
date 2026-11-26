<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    private function rankInRun(int $run, int $pid): ?int
    {
        $json = DB::table('recommendation_runs')->where('id', $run)->value('results');
        foreach (json_decode((string) $json, true) ?? [] as $x) {
            if ((int) $x['portfolio_id'] === $pid) {
                return (int) $x['rank'];
            }
        }

        return null;
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'run_id' => 'required|integer',
            'portfolio_id' => 'required|integer',
            'event' => 'required|in:save,thumbs_up,thumbs_down,dismiss',
        ]);
        $rank = $this->rankInRun((int) $v['run_id'], (int) $v['portfolio_id']);
        abort_if($rank === null, 422, 'Portfolio is not part of this run.');

        DB::table('feedback_events')->insert($v + ['rank' => $rank, 'created_at' => now(), 'updated_at' => now()]);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function go(int $run, int $portfolio)
    {
        $rank = $this->rankInRun($run, $portfolio);
        abort_if($rank === null, 404);
        DB::table('feedback_events')->insert(['run_id' => $run, 'portfolio_id' => $portfolio, 'event' => 'click',
            'rank' => $rank, 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('portfolios.show', $portfolio);
    }
}
