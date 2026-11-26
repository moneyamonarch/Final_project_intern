<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function seedRun(): int
    {
        Portfolio::query()->create(['portfolio_id' => 1, 'dataset' => 'sdg_intervention', 'code' => 'C1', 'title' => 'A',
            'sdg_num' => 1, 'focus_group' => 'G', 'focus_area' => 'F', 'category' => 'C', 'total' => 10, 'is_overview' => false]);

        return DB::table('recommendation_runs')->insertGetId(['algorithm_version' => 't', 'weights' => '{}',
            'results' => json_encode([['portfolio_id' => 1, 'rank' => 1, 'score' => 50, 'f' => ['topic' => 1]]]),
            'share_token' => 'abcdefghijklmnop1234', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'DENY')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_admin_requires_credentials(): void
    {
        config(['admin.user' => 'a', 'admin.pass' => 'b']);
        $this->get('/admin')->assertStatus(401);
        $this->get('/admin', ['Authorization' => 'Basic '.base64_encode('a:b')])->assertOk()->assertSee('Weights history');
    }

    public function test_admin_is_closed_when_not_configured(): void
    {
        config(['admin.user' => null, 'admin.pass' => null]);
        $this->get('/admin', ['Authorization' => 'Basic '.base64_encode(':')])->assertStatus(401);
    }

    public function test_feedback_rejects_portfolio_outside_run_and_accepts_valid(): void
    {
        $run = $this->seedRun();
        $this->postJson('/feedback', ['run_id' => $run, 'portfolio_id' => 99, 'event' => 'save'])->assertStatus(422);
        $this->postJson('/feedback', ['run_id' => $run, 'portfolio_id' => 1, 'event' => 'thumbs_up'])->assertOk();
        $this->assertDatabaseHas('feedback_events', ['run_id' => $run, 'portfolio_id' => 1, 'event' => 'thumbs_up']);
    }

    public function test_click_redirect_logs_and_share_page_renders(): void
    {
        $run = $this->seedRun();
        $this->get("/go/$run/1")->assertRedirect(route('portfolios.show', 1));
        $this->assertDatabaseHas('feedback_events', ['event' => 'click']);
        $this->get('/r/abcdefghijklmnop1234')->assertOk()->assertSee('Shared snapshot');
        $this->get('/r/zzzzzzzzzzzzzzzzzzzz')->assertNotFound();
    }

    public function test_results_page_records_a_run_with_feedback_controls(): void
    {
        foreach ([[1, 1, 'Poverty aid', 10], [2, 2, 'Crop support', 20], [3, 3, 'Clinics', 30]] as [$id, $sdg, $title, $total]) {
            Portfolio::query()->create(['portfolio_id' => $id, 'dataset' => 'sdg_intervention', 'code' => "C$id", 'title' => $title,
                'sdg_num' => $sdg, 'focus_group' => 'Development', 'focus_area' => 'Agriculture & Food', 'category' => 'Cat',
                'total' => $total, 'is_overview' => false]);
        }
        $this->post('/demo/ngo_funder');
        $this->get('/recommendations')->assertOk();
        $this->assertSame(1, DB::table('recommendation_runs')->count());
    }

    public function test_privacy_page_exists(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('What this site stores');
    }
}
