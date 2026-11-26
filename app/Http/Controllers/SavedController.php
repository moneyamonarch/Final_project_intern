<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavedController extends Controller
{
    private const KEY = 'saved_portfolios';

    public function index()
    {
        $ids = session(self::KEY, []);
        $portfolios = Portfolio::whereIn('portfolio_id', $ids)->get()->sortBy(fn ($p) => array_search($p->portfolio_id, $ids))->values();

        return view('saved.index', [
            'portfolios' => $portfolios,
            'collectionNames' => config('recommender.collections', []),
            'amountUnits' => config('recommender.amount_units', []),
        ]);
    }

    /** Save a whole batch of selected portfolios at once. */
    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'save' => ['required', 'array', 'min:1', 'max:200'],
            'save.*' => ['integer', 'exists:portfolios,portfolio_id'],
            'run_id' => ['nullable', 'integer'],
        ], ['save.required' => 'Select at least one portfolio to save.']);

        $new = array_map('intval', $v['save']);
        session([self::KEY => array_values(array_unique(array_merge(session(self::KEY, []), $new)))]);

        if (! empty($v['run_id'])) {
            $ranks = [];
            foreach (json_decode((string) DB::table('recommendation_runs')->where('id', $v['run_id'])->value('results'), true) ?? [] as $x) {
                $ranks[(int) $x['portfolio_id']] = (int) $x['rank'];
            }
            $now = now();
            $rows = [];
            foreach ($new as $pid) {
                if (isset($ranks[$pid])) {
                    $rows[] = ['run_id' => (int) $v['run_id'], 'portfolio_id' => $pid, 'event' => 'save', 'rank' => $ranks[$pid], 'created_at' => $now, 'updated_at' => $now];
                }
            }
            if ($rows !== []) {
                DB::table('feedback_events')->insert($rows);
            }
        }

        return redirect()->route('saved.index')->with('status', count($new).' portfolio(s) saved.');
    }

    /** Remove selected portfolios, or everything when "all" is sent. */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->boolean('all')) {
            session()->forget(self::KEY);

            return redirect()->route('saved.index')->with('status', 'Saved list cleared.');
        }

        $v = $request->validate(['remove' => ['required', 'array'], 'remove.*' => ['integer']], ['remove.required' => 'Select at least one to remove.']);
        $left = array_values(array_diff(session(self::KEY, []), array_map('intval', $v['remove'])));
        session([self::KEY => $left]);

        return redirect()->route('saved.index')->with('status', count($v['remove']).' removed.');
    }
}
