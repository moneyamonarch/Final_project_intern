<?php

namespace App\Services;

class RecommenderService
{
    /** Calculate separate lower and middle tertile cut points per collection. */
    public function calculateAmountBands(array $amountsByCollection): array
    {
        $bands = [];

        foreach ($amountsByCollection as $collection => $amounts) {
            $values = array_values(array_map(
                'floatval',
                array_filter($amounts, static fn ($amount): bool => is_numeric($amount)),
            ));
            sort($values, SORT_NUMERIC);

            if ($values === []) {
                continue;
            }

            $count = count($values);
            $smallIndex = max(0, (int) ceil($count / 3) - 1);
            $mediumIndex = max(0, (int) ceil((2 * $count) / 3) - 1);

            $bands[$collection] = [
                'small_max' => $values[$smallIndex],
                'medium_max' => $values[$mediumIndex],
            ];
        }

        return $bands;
    }

    /** Score, sort, and diversify portfolio records using only visitor answers. */
    public function recommend(
        array $portfolios,
        array $answers,
        array $weights,
        array $collectionNames,
        array $amountBands,
        int $limit = 10,
        int $indicatorCap = 3,
        float $lambda = 0.7,
    ): array {
        $ranked = [];
        $selectedCollections = $answers['collections'] ?? [];

        foreach ($portfolios as $portfolio) {
            if (! empty($portfolio['is_overview'])) {
                continue;
            }

            $dataset = (string) ($portfolio['dataset'] ?? '');

            if ($selectedCollections !== [] && ! in_array($dataset, $selectedCollections, true)) {
                continue;
            }

            $scored = $this->scorePortfolio($portfolio, $answers, $weights, $collectionNames, $amountBands);

            if ($scored['applicable_weight'] <= 0 || $scored['matched_criteria_count'] === 0) {
                continue;
            }

            // M6.2: matched share of what was asked, scaled by sqrt(data coverage) so incomplete rows rank lower.
            $requested = $scored['requested_weight'];
            $coverage = sqrt($scored['applicable_weight'] / $requested);

            $ranked[] = [
                'portfolio' => $portfolio,
                'score' => round(($scored['matched_weight'] / $requested) * $coverage * 100, 2),
                'coverage' => round($coverage, 2),
                'matched_criteria' => $scored['matched_criteria'],
                'matched_criteria_count' => $scored['matched_criteria_count'],
                'reason' => $this->buildReason($scored['matched_criteria']),
                'breakdown' => array_map(static fn (array $p): array => [
                    'key' => $p['key'],
                    'label' => $p['label'],
                    'weight' => $p['weight'],
                    'applicable' => $p['applicable'],
                    'note' => $p['note'],
                    'credit' => $p['credit'],
                    'contribution' => $p['credit'] > 0 ? round($p['weight'] * $p['credit'] / $requested * $coverage * 100, 1) : 0.0,
                ], $scored['breakdown']),
            ];
        }

        foreach ($ranked as &$row) {
            $row['_spec'] = ($row['portfolio']['sdg_num'] ?? null) !== null ? 1 : 0; // SDG-specific rows first
        }
        unset($row);

        $cmp = static fn (array $left, array $right): int => ($right['score'] <=> $left['score'])
            ?: ($right['matched_criteria_count'] <=> $left['matched_criteria_count'])
            ?: ($right['_spec'] <=> $left['_spec']);

        usort($ranked, static fn (array $left, array $right): int => $cmp($left, $right)
            ?: ($left['portfolio']['portfolio_id'] <=> $right['portfolio']['portfolio_id']));

        // Round-robin across collections inside each tied group, so one collection does not fill every tie.
        $seen = [];
        foreach ($ranked as &$row) {
            $tie = $row['score'].'|'.$row['matched_criteria_count'].'|'.$row['_spec'];
            $dataset = (string) ($row['portfolio']['dataset'] ?? '');
            $row['_rr'] = $seen[$tie][$dataset] = ($seen[$tie][$dataset] ?? -1) + 1;
        }
        unset($row);

        usort($ranked, static fn (array $left, array $right): int => $cmp($left, $right)
            ?: ($left['_rr'] <=> $right['_rr'])
            ?: ($left['portfolio']['portfolio_id'] <=> $right['portfolio']['portfolio_id']));

        foreach ($ranked as &$row) {
            unset($row['_spec'], $row['_rr']);
        }
        unset($row);

        return $this->diversify($ranked, $limit, $indicatorCap, $lambda);
    }

    /**
     * Broad sample for visitors who skipped every scoring question: the first row of each
     * focus area, taken round-robin across collections (optionally limited to $collections).
     */
    public function defaultSpread(array $portfolios, array $collections = [], int $limit = 10): array
    {
        $byDataset = [];

        foreach ($portfolios as $portfolio) {
            $dataset = (string) ($portfolio['dataset'] ?? '');

            if (! empty($portfolio['is_overview']) || ($collections !== [] && ! in_array($dataset, $collections, true))) {
                continue;
            }

            $area = (string) ($portfolio['focus_area'] ?? '');
            $byDataset[$dataset][$area] ??= $portfolio; // first row of each focus area
        }

        ksort($byDataset);
        $queues = array_map('array_values', $byDataset);
        $results = [];

        while (count($results) < $limit && array_filter($queues) !== []) {
            foreach ($queues as $dataset => $queue) {
                if ($queue === []) {
                    continue;
                }

                $results[] = [
                    'portfolio' => array_shift($queues[$dataset]),
                    'score' => null,
                    'matched_criteria' => [],
                    'matched_criteria_count' => 0,
                    'reason' => 'Part of a broad sample: one portfolio per focus area, spread across collections.',
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /** True when no scoring question was answered (collections are a filter, not a score). */
    public function hasNoScoringAnswers(array $answers): bool
    {
        return empty($answers['sdgs'])
            && empty($answers['digital_sovereignty'])
            && empty($answers['focus_areas'])
            && in_array($answers['investment_size'] ?? 'no_preference', ['no_preference', null, ''], true);
    }

    /** Per-criterion scoring. Only criteria the visitor actually answered are "requested". */
    private function scorePortfolio(
        array $portfolio,
        array $answers,
        array $weights,
        array $collectionNames,
        array $amountBands,
    ): array {
        $dataset = (string) ($portfolio['dataset'] ?? '');
        $parts = [];
        $add = function (string $key, string $label, float $weight, bool $applicable, array $matches, string $missing, ?float $credit = null) use (&$parts): void {
            $parts[] = [
                'key' => $key, 'label' => $label, 'weight' => $weight, 'applicable' => $applicable, 'matches' => $matches,
                'credit' => $applicable ? ($credit ?? ($matches === [] ? 0.0 : 1.0)) : 0.0,
                'note' => $applicable ? ($matches === [] ? 'no match' : implode(', ', $matches)) : $missing,
            ];
        };

        $topicWeight = (float) ($weights['topic'] ?? 0);
        $sdgs = array_map('intval', $answers['sdgs'] ?? []);
        $digital = (bool) ($answers['digital_sovereignty'] ?? false);

        if ($topicWeight > 0 && ($sdgs !== [] || $digital)) {
            $applicable = false;
            $matches = [];

            if ($sdgs !== [] && ($portfolio['sdg_num'] ?? null) !== null) {
                $applicable = true;
                if (in_array((int) $portfolio['sdg_num'], $sdgs, true)) {
                    $matches[] = 'SDG '.(int) $portfolio['sdg_num'];
                }
            }

            if ($digital && ! empty($portfolio['focus_group'])) {
                $applicable = true;
                if ($portfolio['focus_group'] === 'Digital sovereignty') {
                    $matches[] = 'Digital sovereignty';
                }
            }

            $add('topic', 'SDG / topic', $topicWeight, $applicable, $matches, 'no SDG on this record');
        }

        $focusWeight = (float) ($weights['focus_area'] ?? 0);
        $areas = $answers['focus_areas'] ?? [];

        if ($focusWeight > 0 && $areas !== []) {
            $has = ! empty($portfolio['focus_area']);
            $add('focus_area', 'Focus area', $focusWeight, $has,
                $has && in_array($portfolio['focus_area'], $areas, true) ? [$portfolio['focus_area']] : [], 'no focus area');
        }

        $amountWeight = (float) ($weights['amount_fit'] ?? 0);
        $preference = $answers['investment_size'] ?? 'no_preference';
        $budget = (float) ($answers['budget_npr_bn'] ?? 0);

        if ($amountWeight > 0 && $budget > 0) {
            $total = $portfolio['total'] ?? null;
            $ok = $total !== null && (float) $total > 0 && ($portfolio['amount_unit'] ?? null) === 'NRs billion';
            $raw = $ok ? max(0.0, 1 - min(1.0, abs(log((float) $total) - log($budget * 0.20)) / log(10))) : 0.0;
            $add('amount_fit', 'Amount fit', $amountWeight, $ok,
                $raw > 0 ? [sprintf('Fits your budget (%d%% fit)', round($raw * 100))] : [], 'not yet costed', $raw);
        } elseif ($amountWeight > 0 && in_array($preference, ['small', 'medium', 'large'], true)) {
            $ok = ($portfolio['total'] ?? null) !== null && isset($amountBands[$dataset]);
            $matched = $ok && $this->amountBandFor((float) $portfolio['total'], $amountBands[$dataset]) === $preference;
            $add('amount_fit', 'Amount size', $amountWeight, $ok,
                $matched ? [ucfirst($preference).' amount band within this collection'] : [], 'not yet costed');
        }

        $requested = $applicable = $matchedWeight = 0.0;
        $criteria = [];
        $count = 0;

        foreach ($parts as $p) {
            $requested += $p['weight'];
            $applicable += $p['applicable'] ? $p['weight'] : 0;

            if ($p['applicable'] && $p['credit'] > 0) {
                $matchedWeight += $p['weight'] * $p['credit'];
                $count++;
                $criteria = array_merge($criteria, $p['matches']);
            }
        }

        return [
            'requested_weight' => $requested,
            'applicable_weight' => $applicable,
            'matched_weight' => $matchedWeight,
            'matched_criteria' => array_values(array_unique($criteria)),
            'matched_criteria_count' => $count,
            'breakdown' => $parts,
        ];
    }

    /** M6.6: maximal marginal relevance re-ranking, with the indicator cap applied during selection. */
    private function diversify(array $pool, int $limit, int $indicatorCap, float $lambda): array
    {
        $results = [];
        $indicators = 0;

        while (count($results) < $limit && $pool !== []) {
            $bestKey = null;
            $bestValue = -INF;

            foreach ($pool as $key => $row) {
                if (($row['portfolio']['dataset'] ?? '') === 'sdg_indicator' && $indicators >= $indicatorCap) {
                    unset($pool[$key]);
                    continue;
                }

                $maxSimilarity = 0.0;
                foreach ($results as $chosen) {
                    $maxSimilarity = max($maxSimilarity, $this->similarity($row['portfolio'], $chosen['portfolio']));
                }

                $value = $lambda * ($row['score'] / 100) - (1 - $lambda) * $maxSimilarity;

                if ($value > $bestValue + 1e-12) {
                    $bestValue = $value;
                    $bestKey = $key;
                }
            }

            if ($bestKey === null) {
                break;
            }

            $row = $pool[$bestKey];
            unset($pool[$bestKey]);
            $indicators += ($row['portfolio']['dataset'] ?? '') === 'sdg_indicator' ? 1 : 0;
            $results[] = $row;
        }

        return $results;
    }

    /** 1 = same focus area and same SDG, 0.5 = one of them, 0 = neither. */
    private function similarity(array $a, array $b): float
    {
        $focus = ! empty($a['focus_area']) && ($a['focus_area'] ?? null) === ($b['focus_area'] ?? null);
        $sdg = ($a['sdg_num'] ?? null) !== null && ($a['sdg_num'] ?? null) === ($b['sdg_num'] ?? null);

        return ($focus + $sdg) / 2;
    }

    /** Return a row's amount band using the cut points for its own collection. */
    private function amountBandFor(float $amount, array $band): string
    {
        if ($amount <= $band['small_max']) {
            return 'small';
        }

        if ($amount <= $band['medium_max']) {
            return 'medium';
        }

        return 'large';
    }

    /** Build a reason sentence from matched criteria only. */
    private function buildReason(array $criteria): string
    {
        $criteria = array_values(array_unique($criteria));
        $count = count($criteria);

        if ($count === 1) {
            return 'Matches '.$criteria[0].'.';
        }

        if ($count === 2) {
            return 'Matches '.$criteria[0].' and '.$criteria[1].'.';
        }

        $last = array_pop($criteria);

        return 'Matches '.implode(', ', $criteria).' and '.$last.'.';
    }
}