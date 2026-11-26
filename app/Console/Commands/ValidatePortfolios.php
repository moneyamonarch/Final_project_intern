<?php

namespace App\Console\Commands;

use App\Models\Portfolio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidatePortfolios extends Command
{
    protected $signature = 'portfolios:validate {--json : Print the report as JSON}';

    protected $description = 'Run data-quality checks on imported portfolios and write storage/app/dq_report.json';

    private array $checks = [];

    public function handle(): int
    {
        $this->checks = [];
        $datasets = array_keys(config('recommender.collections', []));

        // Integrity (errors)
        $bad = Portfolio::query()->whereNotIn('dataset', $datasets)->pluck('portfolio_id')->all();
        $this->add('error', 'unknown_dataset', 'Rows with a dataset not in config', $bad);

        $bad = Portfolio::query()->whereNotNull('sdg_num')->where(fn ($q) => $q->where('sdg_num', '<', 1)->orWhere('sdg_num', '>', 17))->pluck('portfolio_id')->all();
        $this->add('error', 'sdg_out_of_range', 'sdg_num outside 1-17', $bad);

        $bad = Portfolio::query()->where('total', '<', 0)->pluck('portfolio_id')->all();
        $this->add('error', 'negative_total', 'Negative totals', $bad);

        // Reconciliation (warnings), tolerance 1%
        $overview = Portfolio::query()->where('dataset', 'sdg_intervention')->where('is_overview', true)->groupBy('sdg_num')->pluck(DB::raw('SUM(total)'), 'sdg_num');
        $children = Portfolio::query()->where('dataset', 'sdg_intervention')->where('is_overview', false)->groupBy('sdg_num')->pluck(DB::raw('SUM(total)'), 'sdg_num');
        $indicators = Portfolio::query()->where('dataset', 'sdg_indicator')->where('is_overview', false)->groupBy('sdg_num')->pluck(DB::raw('SUM(total)'), 'sdg_num');

        $mismatch = [];
        foreach ($overview as $sdg => $ov) {
            $ch = (float) ($children[$sdg] ?? 0);
            if ($this->off((float) $ov, $ch)) {
                $mismatch[] = "SDG {$sdg}: overview ".round((float) $ov, 1).' vs children '.round($ch, 1);
            }
        }
        $this->add('warning', 'overview_vs_children', 'Overview total differs from sum of its interventions by more than 1%', $mismatch);

        $mismatch = [];
        foreach ($indicators as $sdg => $ind) {
            $ch = (float) ($children[$sdg] ?? 0);
            if ($this->off((float) $ind, $ch)) {
                $mismatch[] = "SDG {$sdg}: indicators ".round((float) $ind, 1).' vs interventions '.round($ch, 1);
            }
        }
        $this->add('warning', 'indicator_vs_intervention', 'Indicator totals differ from intervention totals by more than 1%', $mismatch);

        // Completeness (info / warning), non-overview rows only
        $rows = Portfolio::query()->where('is_overview', false);
        foreach (['total' => 'amount', 'sdg_num' => 'SDG', 'document_url' => 'document link'] as $col => $label) {
            $byDataset = (clone $rows)->whereNull($col)->selectRaw('dataset, COUNT(*) as n')->groupBy('dataset')->pluck('n', 'dataset')->all();
            $this->add('warning', "missing_{$col}", "Rows without {$label}, by collection", array_map(fn ($d, $n) => "{$d}: {$n}", array_keys($byDataset), $byDataset), array_sum($byDataset));
        }

        $dups = Portfolio::query()->whereNotNull('title_normalized')->groupBy('title_normalized')->havingRaw('COUNT(*) > 1')->pluck('title_normalized')->all();
        $this->add('info', 'duplicate_titles', 'Titles used by more than one row', $dups);

        $tpl = Portfolio::query()->where('summary_is_template', true)->pluck('portfolio_id')->all();
        $this->add('warning', 'template_summaries', 'Rows with generated boilerplate summaries', $tpl);

        $equal = Portfolio::query()->where('dataset', 'sdg_indicator')->whereNotNull('total')->selectRaw('sdg_num, total, COUNT(*) as n')
            ->groupBy('sdg_num', 'total')->havingRaw('COUNT(*) >= 3')->get()
            ->map(fn ($r) => "SDG {$r->sdg_num}: {$r->n} indicators share {$r->total}")->all();
        $this->add('info', 'equal_share_indicators', 'Indicator amounts that look like an equal split of the parent', $equal);

        return $this->report();
    }

    private function off(float $a, float $b): bool
    {
        $max = max(abs($a), abs($b));

        return $max > 0 && abs($a - $b) / $max > 0.01;
    }

    private function add(string $severity, string $key, string $label, array $items, ?int $count = null): void
    {
        $this->checks[] = [
            'severity' => $severity, 'check' => $key, 'label' => $label,
            'count' => $count ?? count($items), 'sample' => array_slice(array_values($items), 0, 20),
        ];
    }

    private function report(): int
    {
        $summary = ['errors' => 0, 'warnings' => 0, 'infos' => 0];
        foreach ($this->checks as $c) {
            if ($c['count'] > 0) {
                $summary[['error' => 'errors', 'warning' => 'warnings', 'info' => 'infos'][$c['severity']]]++;
            }
        }

        $report = ['generated_at' => now()->toIso8601String(), 'rows' => Portfolio::count(), 'summary' => $summary, 'checks' => $this->checks];
        file_put_contents(storage_path('app/dq_report.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Severity', 'Check', 'Count', 'Sample'], array_map(fn ($c) => [
                $c['severity'], $c['check'], $c['count'], implode('; ', array_slice($c['sample'], 0, 3)),
            ], $this->checks));
            $this->line("Errors: {$summary['errors']}  Warnings: {$summary['warnings']}  Info: {$summary['infos']}  (report: storage/app/dq_report.json)");
        }

        return $summary['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
