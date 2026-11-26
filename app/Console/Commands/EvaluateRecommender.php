<?php

namespace App\Console\Commands;

use App\Http\Controllers\RecommendationController;
use App\Models\Portfolio;
use App\Services\InvestorProfiler;
use App\Services\PortfolioBuilder;
use App\Services\RecommenderService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class EvaluateRecommender extends Command
{
    protected $signature = 'recommender:evaluate';

    protected $description = 'Run every demo persona and report top-10, coverage and diversity';

    public function handle(): int
    {
        $seen = [];
        $rows = [];

        foreach (config('personas') as $key => $p) {
            $store = app('session')->driver('array');
            $store->put('questionnaire', $p['answers'] + ['completed' => true]);
            $req = Request::create('/recommendations');
            $req->setLaravelSession($store);
            $req->attributes->set('no_log', true);

            $out = app(RecommendationController::class)->index($req, app(RecommenderService::class), app(InvestorProfiler::class), app(PortfolioBuilder::class));
            if (! $out instanceof View) {
                $this->warn("$key: redirected (incomplete persona answers)");
                continue;
            }
            $res = $out->getData()['results'];
            $cols = array_count_values(array_map(fn ($r) => $r['portfolio']['dataset'], $res));
            $sdgs = array_unique(array_filter(array_map(fn ($r) => $r['portfolio']['sdg_num'], $res)));
            $ent = 0.0;
            foreach ($cols as $c) {
                $q = $c / max(1, count($res));
                $ent -= $q * log($q, 2);
            }
            foreach ($res as $r) {
                $seen[$r['portfolio']['portfolio_id']] = true;
            }
            $rows[] = [$key, count($res), count($cols), count($sdgs), round($ent, 2), $res[0]['portfolio']['title'] ?? '-'];
        }

        $this->table(['persona', 'items', 'collections', 'SDGs', 'collection entropy', 'top item'], $rows);
        $pool = Portfolio::query()->where('is_overview', false)->count();
        $this->info('Coverage: '.count($seen)." of $pool non-overview rows appear for at least one persona.");

        return self::SUCCESS;
    }
}
