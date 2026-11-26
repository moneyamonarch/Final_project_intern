<?php

namespace Tests\Unit;

use App\Services\PortfolioBuilder;
use PHPUnit\Framework\TestCase;

class PortfolioBuilderTest extends TestCase
{
    private const UNITS = ['sdg_intervention' => 'NRs billion', 'sdg_indicator' => 'NRs billion'];

    private function results(): array
    {
        $out = [];
        foreach ([[1, 'sdg_intervention', 2, 10], [2, 'sdg_intervention', 3, 20], [3, 'sdg_indicator', 4, 15], [4, 'sdg_indicator', 5, 30], [5, 'sdg_intervention', 6, 40]] as [$id, $ds, $sdg, $total]) {
            $out[] = ['score' => 80.0, 'portfolio' => ['portfolio_id' => $id, 'dataset' => $ds, 'sdg_num' => $sdg, 'total' => $total]];
        }

        return $out;
    }

    public function test_mix_respects_budget_and_caps(): void
    {
        $budget = 1e9;
        $mix = (new PortfolioBuilder())->build($this->results(), $budget, self::UNITS, [2, 3, 4]);

        self::assertSame('ok', $mix['status']);
        self::assertLessThanOrEqual($budget, $mix['allocated']);
        self::assertTrue($mix['sdg_ok']);

        foreach ($mix['collection_npr'] as $spent) {
            self::assertLessThanOrEqual($budget * 0.4 + 1, $spent);
        }

        foreach ($mix['items'] as $item) {
            self::assertLessThanOrEqual($budget * 0.25 + 1, $item['allocation_npr']);
        }
    }

    public function test_uncosted_or_unknown_unit_rows_are_not_allocated(): void
    {
        self::assertSame('insufficient', (new PortfolioBuilder())->build($this->results(), 1e9, [], [])['status']);
        self::assertSame('no_budget', (new PortfolioBuilder())->build($this->results(), null, self::UNITS)['status']);
    }
}