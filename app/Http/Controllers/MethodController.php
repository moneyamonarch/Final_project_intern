<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Contracts\View\View;

class MethodController extends Controller
{
    /** Public "how it works" page: live row counts, data gaps, scoring description. */
    public function show(): View
    {
        $counts = Portfolio::query()
            ->selectRaw('dataset, COUNT(*) as n, SUM(CASE WHEN total IS NULL THEN 1 ELSE 0 END) as no_amount, SUM(CASE WHEN sdg_num IS NULL THEN 1 ELSE 0 END) as no_sdg')
            ->groupBy('dataset')->get()->keyBy('dataset');

        return view('method', [
            'names' => config('recommender.collections', []),
            'units' => config('recommender.amount_units', []),
            'weights' => config('recommender.weights', []),
            'counts' => $counts,
            'total' => (int) $counts->sum('n'),
        ]);
    }
}
