<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Services\InvestorProfiler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    private function seedPortfolio(): void
    {
        Portfolio::query()->create(['portfolio_id' => 1, 'dataset' => 'sdg_intervention', 'code' => 'A1', 'title' => 'Rural Water',
            'sdg_num' => 6, 'focus_group' => 'Water', 'focus_area' => 'Water & Sanitation', 'category' => 'Cat',
            'total' => 2.0, 'is_overview' => false]);
    }

    public function test_profile_steps_come_first_and_answers_are_stored(): void
    {
        $this->seedPortfolio();
        $this->get('/questionnaire/start');
        $this->get('/questionnaire/1')->assertOk()->assertSee('Which best describes you?')->assertSee('of 8');
        $this->post('/questionnaire/1', ['investor_type' => 'donor', 'origin' => 'nepal', 'experience' => 'nepal'])->assertRedirect('/questionnaire/2');
        $this->post('/questionnaire/2', ['amount' => '50000000', 'currency' => 'NPR', 'structure' => 'grant', 'horizon' => '5_10'])->assertRedirect('/questionnaire/3');
        $this->post('/questionnaire/3', ['impact_tilt' => '80', 'risk_loss' => '3', 'risk_gamble' => '2', 'risk_comfort' => '4'])->assertRedirect('/questionnaire/4');
        $this->assertSame('donor', session('questionnaire.profile.investor_type'));
    }

    public function test_invalid_profile_value_is_rejected_and_skip_stores_null(): void
    {
        $this->post('/questionnaire/1', ['investor_type' => 'martian'])->assertSessionHasErrors('investor_type');
        $this->post('/questionnaire/1', ['_skip' => '1'])->assertRedirect('/questionnaire/2');
        $this->assertNull(session('questionnaire.profile.investor_type'));
    }

    public function test_full_run_shows_profile_strip_and_budget_context(): void
    {
        $this->seedPortfolio();
        $this->post('/questionnaire/1', ['investor_type' => 'donor']);
        $this->post('/questionnaire/2', ['amount' => '50000000', 'currency' => 'NPR', 'horizon' => '5_10']);
        $this->post('/questionnaire/3', ['impact_tilt' => '80', 'risk_loss' => '3', 'risk_gamble' => '2', 'risk_comfort' => '4']);
        $this->post('/questionnaire/4', ['_skip' => '1']);
        $this->post('/questionnaire/5', ['sdgs' => [6]]);
        $this->post('/questionnaire/6', ['_skip' => '1']);
        $this->post('/questionnaire/7', ['_skip' => '1']);
        $this->post('/questionnaire/8', ['_skip' => '1'])->assertRedirect('/recommendations');
        $this->get('/recommendations')->assertOk()->assertSee('Your profile')->assertSee('Development partner or donor')
            ->assertSee('Balanced risk profile')->assertSee('Impact-led')->assertSee('Rural Water')->assertSee('of this portfolio');
    }

    public function test_risk_conflict_lowers_confidence_and_usd_converts(): void
    {
        $p = new InvestorProfiler();
        $this->assertSame('low', $p->risk(['risk_loss' => '1', 'risk_gamble' => '4'])['confidence']);
        $this->assertNull($p->risk([]));
        $this->assertSame(133000.0, $p->build(['amount' => 1000, 'currency' => 'USD'], config('questionnaire'))['budget_npr']);
    }
}