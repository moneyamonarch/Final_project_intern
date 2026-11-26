<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Show the homepage with live portfolio, SDG, and collection counts. */
    public function index(): View
    {
        $portfolioCount = Portfolio::query()->count();
        $sdgCount = Portfolio::query()
            ->whereNotNull('sdg_num')
            ->distinct()
            ->count('sdg_num');
        $collectionCount = Portfolio::query()
            ->select('dataset')
            ->distinct()
            ->count('dataset');

        $needTotal = (float) Portfolio::query()
            ->where('dataset', 'sdg_intervention')->where('is_overview', false)->sum('total');

        return view('home', compact('portfolioCount', 'sdgCount', 'collectionCount', 'needTotal'));
    }
}
