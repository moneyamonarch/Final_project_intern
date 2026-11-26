<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TrainRecommender extends Command
{
    protected $signature = 'recommender:train {--min=100 : Minimum labeled samples}';

    protected $description = 'Fit logistic regression on feedback and store clamped, normalised criterion weights';

    public function handle(): int
    {
        $keys = ['topic', 'focus_area', 'amount_fit'];
        $runs = DB::table('recommendation_runs')->pluck('results', 'id');
        $samples = [];

        foreach (DB::table('feedback_events')->get() as $e) {
            $k = $e->run_id.'|'.$e->portfolio_id;
            $label = in_array($e->event, ['thumbs_up', 'save', 'click'], true) ? 1 : 0;
            $samples[$k] = ['run' => $e->run_id, 'pid' => $e->portfolio_id, 'y' => max($samples[$k]['y'] ?? 0, $label)];
            if ($e->event === 'thumbs_down' || $e->event === 'dismiss') {
                $samples[$k]['y'] = 0; // explicit negative wins
                $samples[$k]['neg'] = true;
            }
        }

        $X = [];
        $y = [];
        foreach ($samples as $s) {
            $row = collect(json_decode($runs[$s['run']] ?? '[]', true))->firstWhere('portfolio_id', $s['pid']);
            if (! $row) {
                continue;
            }
            $X[] = array_map(fn ($k) => (float) ($row['f'][$k] ?? 0), $keys);
            $y[] = $s['y'];
        }

        $n = count($y);
        if ($n < (int) $this->option('min') || array_sum($y) < 10) {
            $this->error("Need at least {$this->option('min')} labeled samples and 10 positives; have $n samples, ".array_sum($y).' positives.');

            return self::FAILURE;
        }

        $w = array_fill(0, count($keys), 0.0);
        $b = 0.0;
        $loss = fn () => $this->logloss($X, $y, $w, $b);
        $before = $loss();
        for ($it = 0; $it < 500; $it++) {
            $gw = array_fill(0, count($keys), 0.0);
            $gb = 0.0;
            foreach ($X as $i => $x) {
                $z = $b;
                foreach ($x as $j => $v) {
                    $z += $w[$j] * $v;
                }
                $err = 1 / (1 + exp(-$z)) - $y[$i];
                foreach ($x as $j => $v) {
                    $gw[$j] += $err * $v;
                }
                $gb += $err;
            }
            foreach ($w as $j => $_) {
                $w[$j] -= 0.5 * $gw[$j] / $n;
            }
            $b -= 0.5 * $gb / $n;
        }

        $w = array_map(fn ($v) => max(0.05, min(0.50, max(0.0, $v))), $w);
        $sum = array_sum($w);
        $weights = array_combine($keys, array_map(fn ($v) => round($v / $sum, 4), $w));
        $version = (int) DB::table('recommender_weights')->max('version') + 1;

        DB::transaction(function () use ($version, $weights, $n, $before): void {
            DB::table('recommender_weights')->update(['is_active' => false]);
            DB::table('recommender_weights')->insert([
                'version' => $version, 'weights' => json_encode($weights), 'trained_on' => $n,
                'metrics' => json_encode(['logloss_untrained' => round($before, 4)]),
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $this->info("Activated weights v$version: ".json_encode($weights));

        return self::SUCCESS;
    }

    private function logloss(array $X, array $y, array $w, float $b): float
    {
        $s = 0.0;
        foreach ($X as $i => $x) {
            $z = $b;
            foreach ($x as $j => $v) {
                $z += $w[$j] * $v;
            }
            $p = min(1 - 1e-9, max(1e-9, 1 / (1 + exp(-$z))));
            $s += -($y[$i] * log($p) + (1 - $y[$i]) * log(1 - $p));
        }

        return $s / max(1, count($y));
    }
}
