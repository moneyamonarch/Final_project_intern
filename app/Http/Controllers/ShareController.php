<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;

class ShareController extends Controller
{
    public function show(string $token)
    {
        $run = DB::table('recommendation_runs')->where('share_token', $token)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->first();
        abort_if($run === null, 404);

        $rows = collect(json_decode($run->results, true));
        $portfolios = Portfolio::query()->whereIn('portfolio_id', $rows->pluck('portfolio_id'))->get()->keyBy('portfolio_id');

        return view('share', [
            'run' => $run,
            'rows' => $rows,
            'portfolios' => $portfolios,
            'collectionNames' => config('recommender.collections', []),
            'amountUnits' => config('recommender.amount_units', []),
        ]);
    }
}
