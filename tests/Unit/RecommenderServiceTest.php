<?php

namespace Tests\Unit;

use App\Services\RecommenderService;
use PHPUnit\Framework\TestCase;

class RecommenderServiceTest extends TestCase
{
    /** Check five representative answer sets against expected portfolio IDs. */
    public function test_five_sample_answer_sets_return_expected_top_portfolios(): void
    {
        $service = $this->service();
        $cases = [
            [['sdgs' => [2]], [101, 102]],
            [['focus_areas' => ['Agriculture & Food'], 'collections' => ['sdg_intervention']], [101]],
            [['digital_sovereignty' => true], [104]],
            [['investment_size' => 'medium'], [103]],
            [['focus_areas' => ['Agriculture & Food'], 'collections' => ['sdg_indicator']], [102]],
        ];

        foreach ($cases as [$answers, $expectedIds]) {
            $results = $service->recommend(
                $this->samplePortfolios(),
                $answers,
                $this->weights(),
                $this->collectionNames(),
                $this->sampleAmountBands(),
            );

            self::assertSame($expectedIds, array_column(array_column($results, 'portfolio'), 'portfolio_id'));
        }
    }

    /** Rows missing data for an answered question stay in the list but rank below complete rows. */
    public function test_rows_without_amount_rank_below_complete_rows(): void
    {
        $results = $this->service()->recommend(
            $this->samplePortfolios(),
            ['focus_areas' => ['Agriculture & Food'], 'investment_size' => 'large'],
            $this->weights(),
            $this->collectionNames(),
            $this->sampleAmountBands(),
        );
        $scoresById = [];

        foreach ($results as $result) {
            $scoresById[$result['portfolio']['portfolio_id']] = $result['score'];
        }

        self::assertEqualsWithDelta(35.36, $scoresById[106], 0.01); // 50% match x sqrt(50% coverage)
        self::assertSame(50.0, $scoresById[101]);
        self::assertLessThan($scoresById[101], $scoresById[106]);
    }

    /** Ensure overview rows never appear in recommendations. */
    public function test_overview_rows_are_excluded(): void
    {
        $results = $this->service()->recommend(
            $this->samplePortfolios(),
            ['sdgs' => [2]],
            $this->weights(),
            $this->collectionNames(),
            $this->sampleAmountBands(),
        );
        $ids = array_column(array_column($results, 'portfolio'), 'portfolio_id');

        self::assertNotContains(107, $ids);
    }

    /** Ensure at most the configured number of indicator rows enter the top ten. */
    public function test_indicator_cap_is_respected_and_other_collections_fill_results(): void
    {
        $portfolios = [];

        for ($id = 1; $id <= 10; $id++) {
            $portfolios[] = $this->portfolio($id, 'sdg_indicator');
            $portfolios[] = $this->portfolio($id + 100, 'sdg_intervention');
        }

        $results = $this->service()->recommend(
            $portfolios,
            ['sdgs' => [2], 'focus_areas' => ['Agriculture & Food']],
            $this->weights(),
            $this->collectionNames(),
            [],
            10,
            3,
        );
        $indicatorCount = count(array_filter(
            $results,
            static fn (array $result): bool => $result['portfolio']['dataset'] === 'sdg_indicator',
        ));

        self::assertCount(10, $results);
        self::assertSame(3, $indicatorCount);
    }

    /** Ensure reason text contains only the criteria that matched. */
    public function test_reason_lists_only_real_matches(): void
    {
        $results = $this->service()->recommend(
            $this->samplePortfolios(),
            ['sdgs' => [2], 'focus_areas' => ['Agriculture & Food']],
            $this->weights(),
            $this->collectionNames(),
            $this->sampleAmountBands(),
        );

        self::assertSame('Matches SDG 2 and Agriculture & Food.', $results[0]['reason']);
        self::assertSame(['SDG 2', 'Agriculture & Food'], $results[0]['matched_criteria']);
    }

    /** M6.6: a near-duplicate of an already chosen row is pushed below a different one. */
    public function test_diversity_reranking_prefers_a_different_row_second(): void
    {
        $rows = [
            $this->portfolio(1, 'sdg_intervention', 2, 'Development', 'Agriculture & Food', '20.00'),
            $this->portfolio(2, 'sdg_intervention', 2, 'Development', 'Agriculture & Food', '20.00'),
            $this->portfolio(3, 'sdg_intervention', 2, 'Development', 'Agriculture & Food', '20.00'),
            $this->portfolio(4, 'sdg_intervention', 3, 'Development', 'Health', '20.00'),
        ];
        $results = $this->service()->recommend($rows, ['sdgs' => [2, 3]], $this->weights(), $this->collectionNames(), []);

        self::assertSame([1, 4], array_slice(array_column(array_column($results, 'portfolio'), 'portfolio_id'), 0, 2));
        self::assertNotEmpty($results[0]['breakdown']);
    }

    /** Create the recommender used by these isolated service tests. */
    private function service(): RecommenderService
    {
        return new RecommenderService();
    }

    /** Return small portfolio records using values present in the supplied data. */
    private function samplePortfolios(): array
    {
        return [
            $this->portfolio(101, 'sdg_intervention', 2, 'Development', 'Agriculture & Food', '20.00'),
            $this->portfolio(102, 'sdg_indicator', 2, 'Development', 'Agriculture & Food', '40.00'),
            $this->portfolio(103, 'sdg_intervention', 1, 'Development', 'Poverty, Gender & Social Inclusion', '80.00'),
            $this->portfolio(104, 'sovereign', null, 'Digital sovereignty', 'AI, Algorithms & AI Governance', null),
            $this->portfolio(105, 'nap', null, 'Development', 'Climate, Forests & Disaster Resilience', '5.00'),
            $this->portfolio(106, 'draft', null, 'Development', 'Agriculture & Food', null),
            $this->portfolio(107, 'sdg_intervention', 2, 'Development', 'Agriculture & Food', '10.00', true),
        ];
    }

    /** Build one minimal portfolio row for a service test. */
    private function portfolio(
        int $id,
        string $dataset,
        ?int $sdgNumber = 2,
        string $focusGroup = 'Development',
        string $focusArea = 'Agriculture & Food',
        ?string $total = '20.00',
        bool $overview = false,
    ): array {
        return [
            'portfolio_id' => $id,
            'dataset' => $dataset,
            'sdg_num' => $sdgNumber,
            'focus_group' => $focusGroup,
            'focus_area' => $focusArea,
            'total' => $total,
            'is_overview' => $overview,
        ];
    }

    /** Supply equal editable weights for isolated scoring tests. */
    private function weights(): array
    {
        return ['topic' => 0.25, 'focus_area' => 0.25, 'collection' => 0.25, 'amount_fit' => 0.25];
    }

    /** Supply readable names for known collection keys. */
    private function collectionNames(): array
    {
        return [
            'sdg_intervention' => 'SDG Interventions',
            'sdg_indicator' => 'SDG Indicators',
            'nap' => 'Climate NAP 2021-2050',
            'sovereign' => 'Digital/AI Sovereign',
            'draft' => 'Draft Portfolios',
        ];
    }

    /** Supply amount cut points calculated separately per sample collection. */
    private function sampleAmountBands(): array
    {
        return $this->service()->calculateAmountBands([
            'sdg_intervention' => [20, 80],
            'sdg_indicator' => [40],
            'nap' => [5],
        ]);
    }
}