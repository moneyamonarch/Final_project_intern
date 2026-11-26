<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    /** Side-by-side table for 2 or 3 portfolios chosen on the results page. */
    public function show(Request $request): View
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', (array) $request->query('ids'))))), 0, 3);
        $portfolios = $ids === [] ? collect() : Portfolio::query()->whereIn('portfolio_id', $ids)->get()->sortBy(fn ($p) => array_search($p->portfolio_id, $ids))->values();

        return view('compare', [
            'portfolios' => $portfolios,
            'collectionNames' => config('recommender.collections', []),
            'amountUnits' => config('recommender.amount_units', []),
        ]);
    }
}