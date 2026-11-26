<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Services\InvestorProfiler;
use App\Services\PortfolioBuilder;
use App\Services\RecommenderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecommendationController extends Controller
{
    private const ALGORITHM = '2.0 (coverage-adjusted, diversity re-ranked)';

    /** Score the completed questionnaire against current portfolio records. */
    public function index(Request $request, RecommenderService $recommender, InvestorProfiler $profiler, PortfolioBuilder $builder): View|RedirectResponse
    {
        $data = $this->build($request, $recommender, $profiler, $builder);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        if (! $request->attributes->get('no_log') && $data['results'] !== []) {
            $now = now();
            $token = \Illuminate\Support\Str::random(24);
            $rows = [];
            foreach (array_values($data['results']) as $i => $r) {
                $rows[] = [
                    'portfolio_id' => $r['portfolio']['portfolio_id'], 'rank' => $i + 1, 'score' => $r['score'] ?? null,
                    'f' => collect($r['breakdown'] ?? [])->mapWithKeys(fn ($b) => [$b['key'] => (float) ($b['credit'] ?? 0)])->all(),
                ];
            }
            $runId = \Illuminate\Support\Facades\DB::table('recommendation_runs')->insertGetId([
                'algorithm_version' => self::ALGORITHM, 'weights' => json_encode($data['weights']),
                'profile' => json_encode($data['profile'] ?? []), 'results' => json_encode($rows),
                'share_token' => $token, 'expires_at' => $now->copy()->addDays(30), 'created_at' => $now, 'updated_at' => $now,
            ]);
            \Illuminate\Support\Facades\DB::table('feedback_events')->insert(array_map(fn ($x) => [
                'run_id' => $runId, 'portfolio_id' => $x['portfolio_id'], 'event' => 'impression', 'rank' => $x['rank'],
                'is_synthetic' => ! empty($data['persona']), 'created_at' => $now, 'updated_at' => $now,
            ], $rows));
            $data['run_id'] = $runId;
            $data['share_token'] = $token;
        }

        return view('recommendations.index', $data);
    }

    /** M8.7: CSV of the top results with score, completeness and per-criterion points. */
    public function export(Request $request, RecommenderService $recommender, InvestorProfiler $profiler, PortfolioBuilder $builder): StreamedResponse|RedirectResponse
    {
        $data = $this->build($request, $recommender, $profiler, $builder);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
            $put = static fn (array $row) => fputcsv($out, array_map([self::class, 'csvSafe'], $row), ',', '"', '\\');

            $put(['rank', 'title', 'collection', 'sdg', 'focus_area', 'amount', 'amount_unit', 'match_percent',
                'data_completeness_percent', 'topic_points', 'focus_area_points', 'amount_points', 'reason']);

            foreach ($data['results'] as $i => $res) {
                $p = $res['portfolio'];
                $pts = [];
                foreach ($res['breakdown'] ?? [] as $b) {
                    $pts[$b['key']] = $b['applicable'] ? $b['contribution'] : 'not scored';
                }
                $put([
                    $i + 1,
                    $p['title'],
                    $data['collectionNames'][$p['dataset']] ?? $p['dataset'],
                    $p['sdg_num'] ?? '',
                    $p['focus_area'] ?? '',
                    $p['total'] ?? 'Not yet costed',
                    $p['total'] === null ? '' : ($data['amountUnits'][$p['dataset']] ?? 'unit not stated'),
                    $res['score'] ?? '',
                    isset($res['coverage']) ? round($res['coverage'] * 100) : '',
                    $pts['topic'] ?? '',
                    $pts['focus_area'] ?? '',
                    $pts['amount_fit'] ?? '',
                    $res['reason'],
                ]);
            }

            fclose($out);
        }, 'jtech-recommendations.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Prefix cells that a spreadsheet could run as a formula. */
    public static function csvSafe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /** Shared computation for the page and the export. */
    private function build(Request $request, RecommenderService $recommender, InvestorProfiler $profiler, PortfolioBuilder $builder): array|RedirectResponse
    {
        $questionnaire = $request->session()->get('questionnaire', []);

        if (empty($questionnaire['completed'])) {
            // Never wipe answers: send the visitor to the first step they have not answered.
            return redirect()->route('questionnaire.show', ['step' => $this->firstMissingStep($questionnaire)]);
        }

        $answers = [
            'sdgs' => $questionnaire['topics']['sdgs'] ?? [],
            'digital_sovereignty' => (bool) ($questionnaire['topics']['digital_sovereignty'] ?? false),
            'focus_areas' => $questionnaire['focus_areas'] ?? [],
            'investment_size' => $questionnaire['investment_size'] ?? 'no_preference',
            'collections' => $questionnaire['collections'] ?? [],
        ];

        $profile = $profiler->build($questionnaire['profile'] ?? [], config('questionnaire'));
        $weights = $profile['answered'] > 0
            ? $profiler->weights(config('recommender.weights', []), $profile)
            : config('recommender.weights', []);
        $learned = \Illuminate\Support\Facades\DB::table('recommender_weights')->where('is_active', true)->value('weights');
        if ($learned) {
            $weights = json_decode($learned, true) + $weights;
        }
        $answers['budget_npr_bn'] = ! empty($profile['budget_npr']) ? $profile['budget_npr'] / 1e9 : null;

        $portfolios = Portfolio::query()->get()->toArray();

        $amountRows = Portfolio::query()->whereNotNull('total')->orderBy('dataset')->orderBy('total')->get(['dataset', 'total']);
        $amountsByCollection = [];
        foreach ($amountRows as $row) {
            $amountsByCollection[$row->dataset][] = (float) $row->total;
        }
        $amountBands = $recommender->calculateAmountBands($amountsByCollection);

        $isSample = $recommender->hasNoScoringAnswers($answers);
        $candidates = $isSample
            ? $recommender->defaultSpread($portfolios, $answers['collections'], 10)
            : $recommender->recommend(
                $portfolios,
                $answers,
                $weights,
                config('recommender.collections', []),
                $amountBands,
                40,
                (int) config('recommender.indicator_cap', 3),
                $profiler->lambda($profile),
            );
        $results = array_slice($candidates, 0, 10);
        $mix = $isSample
            ? null
            : $builder->build($candidates, $profile['budget_npr'], config('recommender.amount_units', []), $answers['sdgs']);

        $units = config('recommender.amount_units', []);

        foreach ($results as &$r) {   // context only: share of each portfolio the budget could fund
            $unit = $units[$r['portfolio']['dataset']] ?? null;
            $r['budget_share'] = ($profile['budget_npr'] && $unit === 'NRs billion' && ! empty($r['portfolio']['total']))
                ? $profile['budget_npr'] / ((float) $r['portfolio']['total'] * 1e9) * 100 : null;
        }
        unset($r);

        // M8.1 headline numbers
        $costed = array_filter($results, static fn ($r) => ! empty($r['portfolio']['total']) && ($units[$r['portfolio']['dataset']] ?? null) === 'NRs billion');
        $scored = array_filter($results, static fn ($r) => $r['score'] !== null);
        $resultSdgs = array_values(array_unique(array_filter(array_map(static fn ($r) => (int) ($r['portfolio']['sdg_num'] ?? 0), $results))));
        $headline = [
            'items' => count($results),
            'need' => array_sum(array_map(static fn ($r) => (float) $r['portfolio']['total'], $costed)),
            'costed' => count($costed),
            'sdgs' => count($resultSdgs),
            'completeness' => $scored === [] ? null : (int) round(array_sum(array_column($scored, 'coverage')) / count($scored) * 100),
        ];

        // M7.5 / M8.6: funding need per SDG from non-overview interventions (children, not overview totals)
        $chosen = array_values(array_unique(array_map('intval', $answers['sdgs'])));
        $sdgIds = $chosen !== [] ? $chosen : $resultSdgs;
        sort($sdgIds);
        $needChart = [];
        if ($sdgIds !== []) {
            $sums = Portfolio::query()->where('dataset', 'sdg_intervention')->where('is_overview', false)
                ->whereIn('sdg_num', $sdgIds)->selectRaw('sdg_num, SUM(total) as need, COUNT(*) as n')
                ->groupBy('sdg_num')->get()->keyBy('sdg_num');
            foreach ($sdgIds as $id) {
                $needChart[] = ['sdg' => $id, 'need' => (float) ($sums[$id]->need ?? 0), 'rows' => (int) ($sums[$id]->n ?? 0)];
            }
        }
        $sdgCoverage = $chosen === [] ? null : [
            'chosen' => count($chosen),
            'covered' => count(array_intersect($chosen, $resultSdgs)),
            'missing' => array_values(array_diff($chosen, $resultSdgs)),
        ];

        return [
            'profile' => $profile,
            'isSample' => $isSample,
            'mix' => $mix,
            'persona' => $questionnaire['persona'] ?? null,
            'results' => $results,
            'headline' => $headline,
            'needChart' => $needChart,
            'sdgCoverage' => $sdgCoverage,
            'weights' => $weights,
            'algorithm' => self::ALGORITHM,
            'collectionNames' => config('recommender.collections', []),
            'amountUnits' => $units,
        ];
    }

    /** First questionnaire step with no saved answer (profile steps 1-3, then SDGs, areas, size, collections). */
    private function firstMissingStep(array $questionnaire): int
    {
        $steps = config('questionnaire.profile_steps', []);

        foreach ($steps as $number => $step) {
            foreach ($step['questions'] as $question) {
                if (! array_key_exists($question['key'], $questionnaire['profile'] ?? [])) {
                    return (int) $number;
                }
            }
        }

        $offset = count($steps);

        foreach (['topics' => 1, 'focus_areas' => 2, 'investment_size' => 3, 'collections' => 4] as $key => $number) {
            if (! array_key_exists($key, $questionnaire)) {
                return $offset + $number;
            }
        }

        return 1;
    }
}