<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class M8Test extends TestCase
{
    use RefreshDatabase;

    private function seedRows(): void
    {
        foreach ([[1, 1, '=HYPERLINK("x")', 10], [2, 2, 'Crop support', 20], [3, 3, 'Clinics', 30]] as [$id, $sdg, $title, $total]) {
            Portfolio::query()->create(['portfolio_id' => $id, 'dataset' => 'sdg_intervention', 'code' => "C$id", 'title' => $title,
                'sdg_num' => $sdg, 'focus_group' => 'Development', 'focus_area' => 'Agriculture & Food', 'category' => 'Cat',
                'total' => $total, 'is_overview' => false]);
        }
    }

    public function test_results_show_headline_chart_compare_and_disclaimer(): void
    {
        $this->seedRows();
        $this->post('/demo/ngo_funder');

        $this->get('/recommendations')->assertOk()
            ->assertSee('At a glance')->assertSee('Funding need by SDG')->assertSee('Compare selected')
            ->assertSee('Download CSV')->assertSee('Scoring v2.0')->assertSee('not financial advice');
    }

    public function test_csv_export_has_rows_and_neutralises_formulas(): void
    {
        $this->seedRows();
        $this->post('/demo/ngo_funder');

        $response = $this->get('/recommendations/export')->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Crop support', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_export_without_answers_redirects(): void
    {
        $this->get('/recommendations/export')->assertRedirect();
    }

    public function test_compare_table_and_unsafe_document_link(): void
    {
        $this->seedRows();
        DB::table('portfolios')->where('portfolio_id', 2)->update(['document_url' => 'javascript:alert(1)']);

        $this->get('/compare?ids[]=2&ids[]=3')->assertOk()->assertSee('Crop support')->assertSee('Clinics')
            ->assertDontSee('href="javascript:', false);
        $this->get('/compare?ids[]=2')->assertOk()->assertSee('Pick at least two');
    }
}