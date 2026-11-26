<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Support\Facets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class PortfolioController extends Controller
{
    /** Show filterable portfolios using choices read from the database. */
    public function index(Request $request): View
    {
        $datasets = Facets::datasets();
        $focusAreas = Facets::focusAreas();
        $sdgNumbers = Facets::sdgNumbers();
        $layers = Facets::layers();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'dataset' => ['nullable', 'string', Rule::in($datasets)],
            'focus_area' => ['nullable', 'string', Rule::in($focusAreas)],
            'sdg_num' => ['nullable', 'integer', Rule::in($sdgNumbers)],
            'layer' => ['nullable', 'string', Rule::in($layers)],
            'sort' => ['nullable', Rule::in(['default', 'newest', 'amount'])],
        ]);

        $query = Portfolio::query();

        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        foreach (['dataset', 'focus_area', 'sdg_num', 'layer'] as $filter) {
            if (isset($filters[$filter]) && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (($filters['sort'] ?? 'default') === 'amount') {
            $query->orderBy('total', 'desc')->orderBy('portfolio_id', 'desc');
        } else {
            // Default: collections in the configured order, then largest amount first.
            $order = array_keys(config('recommender.collections', []));

            if ($order !== []) {
                $case = 'CASE dataset'.str_repeat(' WHEN ? THEN ?', count($order)).' ELSE 99 END';
                $bindings = [];

                foreach ($order as $index => $key) {
                    array_push($bindings, $key, $index);
                }

                $query->orderByRaw($case, $bindings);
            }

            $query->orderBy('total', 'desc')->orderBy('portfolio_id');
        }

        $portfolios = $query->paginate(12)->withQueryString();
        $amountUnits = config('recommender.amount_units', []);
        $collectionNames = config('recommender.collections', []);

        return view('portfolios.index', compact(
            'portfolios', 'datasets', 'focusAreas', 'sdgNumbers', 'layers', 'filters', 'amountUnits', 'collectionNames'
        ));
    }

    /** Show every source field and up to four related records. */
    public function show(Portfolio $portfolio): View
    {
        $similarPortfolios = collect();
        $baseQuery = Portfolio::query()
            ->where('focus_area', $portfolio->focus_area)
            ->where('portfolio_id', '!=', $portfolio->portfolio_id)
            ->where('is_overview', false);

        if ($portfolio->sdg_num !== null) {
            $similarPortfolios = (clone $baseQuery)
                ->where('sdg_num', $portfolio->sdg_num)
                ->orderBy('portfolio_id')
                ->limit(4)
                ->get();
        }

        if ($similarPortfolios->count() < 4) {
            $similarIds = $similarPortfolios->pluck('portfolio_id')->all();
            $moreSimilar = (clone $baseQuery)
                ->when($portfolio->sdg_num !== null, fn (Builder $query) => $query->where(function (Builder $sdgQuery) use ($portfolio): void {
                    $sdgQuery->whereNull('sdg_num')->orWhere('sdg_num', '!=', $portfolio->sdg_num);
                }))
                ->when($similarIds !== [], fn (Builder $query) => $query->whereNotIn('portfolio_id', $similarIds))
                ->orderBy('portfolio_id')
                ->limit(4 - $similarPortfolios->count())
                ->get();

            $similarPortfolios = $similarPortfolios->concat($moreSimilar);
        }

        $url = $portfolio->document_url;
        $scheme = $url ? strtolower((string) parse_url($url, PHP_URL_SCHEME)) : '';
        $documentUrl = $url && filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true)
            ? $url
            : null;
        $collectionNames = config('recommender.collections', []);
        $amountUnits = config('recommender.amount_units', []);

        return view('portfolios.show', compact(
            'portfolio', 'similarPortfolios', 'documentUrl', 'collectionNames', 'amountUnits'
        ));
    }
}
