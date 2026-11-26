<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonaTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_persona_opens_results_with_demo_ribbon(): void
    {
        foreach (array_keys(config('personas')) as $key) {
            $this->post("/demo/$key")->assertRedirect('/recommendations');
            $this->get('/recommendations')->assertOk()->assertSee('Demo profile');
            $this->assertTrue(session('questionnaire.completed'));
        }
    }

    public function test_unknown_persona_is_404(): void
    {
        $this->post('/demo/nope')->assertNotFound();
    }

    public function test_home_and_first_question_offer_sample_profiles(): void
    {
        $this->get('/')->assertOk()->assertSee('Try a sample profile');
        $this->get('/questionnaire/1')->assertOk()->assertSee('Try a sample profile');
    }

    public function test_impact_investor_matches_an_sdg_7_row(): void
    {
        Portfolio::query()->create(['portfolio_id' => 1, 'dataset' => 'sdg_intervention', 'code' => 'C1', 'title' => 'Solar mini-grids',
            'sdg_num' => 7, 'focus_group' => 'Development', 'focus_area' => 'Energy & Water Resources', 'category' => 'Energy',
            'total' => 12.5, 'is_overview' => false]);

        $this->post('/demo/impact_investor');
        $this->get('/recommendations')->assertOk()->assertSee('Solar mini-grids')->assertSee('Why this score?');
    }

    public function test_skip_all_persona_has_no_empty_profile_strip(): void
    {
        $this->post('/demo/skip_all');
        $this->get('/recommendations')->assertOk()->assertDontSee('Your profile');
    }
}