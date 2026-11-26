<?php

namespace App\Services;

/** Budget-aware suggested mix built from scored results. Context only, not advice. */
class PortfolioBuilder
{
    private const MAX_ITEMS = 8;
    private const ITEM_CAP = 0.25;        // max share of budget in one item
    private const COLLECTION_CAP = 0.40;  // max share of budget in one collection

    public function build(array $results, ?float $budgetNpr, array $units, array $selectedSdgs = []): array
    {
        if (! $budgetNpr || $budgetNpr <= 0) {
            return ['status' => 'no_budget'];
        }

        $pool = [];
        foreach (array_slice($results, 0, 40) as $r) {
            $p = $r['portfolio'];
            if (($units[$p['dataset'] ?? ''] ?? null) !== 'NRs billion' || empty($p['total']) || ($r['score'] ?? null) === null) {
                continue;   // only costed rows with a known unit
            }
            $r['_npr'] = (float) $p['total'] * 1e9;
            $r['_density'] = $r['score'] / $r['_npr'];
            $pool[] = $r;
        }

        if (count($pool) < 3) {
            return ['status' => 'insufficient'];
        }

        usort($pool, static fn (array $a, array $b): int => $b['_density'] <=> $a['_density']);

        // When 3+ SDGs were chosen, seed the mix with the best item of up to 3 of them.
        $ordered = [];
        $taken = [];
        $selectedSdgs = array_values(array_unique(array_map('intval', $selectedSdgs)));
        if (count($selectedSdgs) >= 3) {
            foreach ($selectedSdgs as $sdg) {
                foreach ($pool as $r) {
                    if ((int) ($r['portfolio']['sdg_num'] ?? 0) === $sdg && ! isset($taken[$r['portfolio']['portfolio_id']])) {
                        $ordered[] = $r;
                        $taken[$r['portfolio']['portfolio_id']] = true;
                        break;
                    }
                }
                if (count($ordered) >= 3) {
                    break;
                }
            }
        }
        foreach ($pool as $r) {
            if (! isset($taken[$r['portfolio']['portfolio_id']])) {
                $ordered[] = $r;
            }
        }

        $left = $budgetNpr;
        $byCollection = [];
        $items = [];

        foreach ($ordered as $r) {
            if (count($items) >= self::MAX_ITEMS || $left < $budgetNpr * 0.01) {
                break;
            }
            $dataset = $r['portfolio']['dataset'];
            $alloc = min(
                $r['_npr'],
                $budgetNpr * self::ITEM_CAP,
                $left,
                $budgetNpr * self::COLLECTION_CAP - ($byCollection[$dataset] ?? 0),
            );
            if ($alloc <= 0) {
                continue;
            }
            $left -= $alloc;
            $byCollection[$dataset] = ($byCollection[$dataset] ?? 0) + $alloc;
            $items[] = [
                'portfolio' => $r['portfolio'],
                'score' => $r['score'],
                'allocation_npr' => $alloc,
                'fraction' => $alloc / $r['_npr'],
            ];
        }

        $allocated = $budgetNpr - $left;
        $sdgCount = count(array_unique(array_filter(array_map(static fn ($i) => $i['portfolio']['sdg_num'] ?? null, $items))));

        return [
            'status' => 'ok',
            'budget' => $budgetNpr,
            'allocated' => $allocated,
            'unallocated' => $left,
            'items' => $items,
            'collection_npr' => $byCollection,
            'collection_shares' => array_map(static fn ($v) => $allocated > 0 ? $v / $allocated * 100 : 0, $byCollection),
            'sdg_count' => $sdgCount,
            'sdg_ok' => count($selectedSdgs) < 3 || $sdgCount >= 3,
        ];
    }
}