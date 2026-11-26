<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Services\RecommenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M1HotfixTest extends TestCase
{
    use RefreshDatabase;

    private function seedMany(): void
    {
        $id = 1;

        foreach (['sdg_intervention' => 2, 'sdg_indicator' => 2, 'nap' => null, 'sovereign' => null, 'draft' => null] as $dataset => $sdg) {
            foreach (['Agriculture & Food', 'Health', 'Energy'] as $area) {
                Portfolio::query()->create([
                    'portfolio_id' => $id,
                    'dataset' => $dataset,
                    'code' => "C{$id}",
                    'title' => "{$dataset} {$area}",
                    'sdg_num' => $sdg,
                    'focus_group' => 'Development',
                    'focus_area' => $area,
                    'category' => 'Cat',
                    'total' => $sdg ? 5.0 + $id : null,
                    'is_overview' => false,
                ]);
                $id++;
            }
        }
    }

    private function skipAll(): void
    {
        foreach (range(1, 8) as $step) {
            $this->post("/questionnaire/{$step}", ['_skip' => '1']);
        }
    }

    public function test_skip_all_shows_broad_sample_from_at_least_three_collections(): void
    {
        $this->seedMany();
        $this->skipAll();

        $this->get('/recommendations')
            ->assertOk()
            ->assertSee('broad sample')
            ->assertDontSee('No matches yet');

        $results = app(RecommenderService::class)->defaultSpread(Portfolio::all()->toArray());

        $this->assertCount(10, $results);
        $this->assertGreaterThanOrEqual(
            3,
            count(array_unique(array_column(array_column($results, 'portfolio'), 'dataset'))),
        );
    }

    public function test_collections_only_does_not_give_every_row_100_percent(): void
    {
        $service = new RecommenderService();

        $this->assertTrue($service->hasNoScoringAnswers(['collections' => ['sdg_intervention']]));
        $this->assertFalse($service->hasNoScoringAnswers(['sdgs' => [2]]));

        $this->seedMany();
        $spread = $service->defaultSpread(Portfolio::all()->toArray(), ['sdg_intervention']);

        $this->assertSame(
            ['sdg_intervention'],
            array_values(array_unique(array_column(array_column($spread, 'portfolio'), 'dataset'))),
        );
        $this->assertNull($spread[0]['score']);
    }

    public function test_opening_results_early_keeps_answers_and_points_to_first_missing_step(): void
    {
        $this->post('/questionnaire/1', ['investor_type' => 'donor']);

        $this->get('/recommendations')->assertRedirect('/questionnaire/2');
        $this->assertSame('donor', session('questionnaire.profile.investor_type'));
    }

    public function test_editing_an_earlier_step_after_completion_returns_to_results(): void
    {
        $this->seedMany();
        $this->skipAll();

        $this->post('/questionnaire/6', ['focus_areas' => ['Health']])
            ->assertRedirect('/recommendations');

        $this->assertTrue(session('questionnaire.completed'));
    }

    public function test_browse_default_sort_is_collection_order_then_amount(): void
    {
        $this->seedMany();

        $this->get('/portfolios')
            ->assertOk()
            ->assertSee('Collection order, largest amount first');

        $this->get('/portfolios?sort=newest')->assertOk();
        $this->get('/portfolios?sort=amount')->assertOk();
    }

    public function test_custom_error_pages_render(): void
    {
        $this->get('/no-such-page')
            ->assertStatus(404)
            ->assertSee('Page not found')
            ->assertSee('Go home');

        $this->assertStringContainsString('Your session expired', view('errors.419')->render());
        $this->assertStringContainsString('Too many requests', view('errors.429')->render());
        $this->assertStringContainsString('Something went wrong', view('errors.500')->render());
    }

    public function test_facets_are_cached_and_flushed(): void
    {
        $this->seedMany();

        $this->assertCount(5, \App\Support\Facets::datasets());

        Portfolio::query()->where('dataset', 'draft')->delete();

        $this->assertCount(5, \App\Support\Facets::datasets());

        \App\Support\Facets::flush();

        $this->assertCount(4, \App\Support\Facets::datasets());
    }
}
