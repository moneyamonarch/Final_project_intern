<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportPortfoliosTest extends TestCase
{
    use RefreshDatabase;

    private const HEAD = 'portfolio_id,dataset,code,title,summary,sdg_num,focus_group,focus_area,category,layer,total,is_overview,document_url';

    private function csv(array $lines, ?string $head = self::HEAD): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pf').'.csv';
        file_put_contents($path, implode("\n", array_merge($head === null ? [] : [$head], $lines))."\n");

        return $path;
    }

    private function good(): array
    {
        return [
            '1,sdg_intervention,A1,Water  supply,"Line one'."\n".'line two",6,Development,Water,Water,,10.5,0,',
            '2,nap,N1,Climate plan,"NAP 2021-2050 priority programme x, upgraded with digital monitoring and AI analytics",,Development,Climate,Climate,,50,0,https://example.org/d',
        ];
    }

    public function test_valid_file_imports_and_is_idempotent_with_derived_columns(): void
    {
        $path = $this->csv($this->good());
        $this->artisan('portfolios:import', ['--path' => $path])->assertExitCode(0);
        $this->artisan('portfolios:import', ['--path' => $path])->assertExitCode(0);

        $this->assertSame(2, Portfolio::count());
        $a = Portfolio::find(1);
        $this->assertSame('water supply', $a->title_normalized);
        $this->assertStringContainsString("\n", $a->summary);   // quoted multi-line summary survives
        $this->assertSame('NRs billion', $a->amount_unit);
        $this->assertTrue(Portfolio::find(2)->summary_is_template);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('portfolios:import', ['--path' => $this->csv($this->good()), '--dry-run' => true])->assertExitCode(0);
        $this->assertSame(0, Portfolio::count());
    }

    public function test_reordered_columns_and_bom_are_accepted(): void
    {
        $cols = explode(',', self::HEAD);
        $order = array_reverse(array_keys($cols));
        $head = "\xEF\xBB\xBF".implode(',', array_map(fn ($i) => $cols[$i], $order));
        $row = fn (array $f) => implode(',', array_map(fn ($i) => $f[$i], $order));
        $path = $this->csv([$row(['7', 'draft', 'D7', 'Idea', 'Short', '8', 'Development', 'Jobs', 'Jobs', '', '', '0', ''])], $head);

        $this->artisan('portfolios:import', ['--path' => $path])->assertExitCode(0);
        $this->assertSame(8, Portfolio::find(7)->sdg_num);
    }

    public function test_every_error_is_reported_and_nothing_is_written(): void
    {
        $path = $this->csv([
            '1,mystery,A1,T,S,6,G,F,C,,1,0,',          // unknown dataset
            '2,nap,A1,T,S,18,G,F,C,,1,0,',             // duplicate code + sdg 18
            '3,nap,A3,T,S,5,G,F,C,,-4,0,',             // negative total
            '4,nap,A4,T,S,5,G,F,C,,1,2,',              // bad is_overview
            '5,nap,A5,T,S,5,G,F,C,,1,0,javascript:x',  // bad url
        ]);

        $this->artisan('portfolios:import', ['--path' => $path])
            ->expectsOutputToContain('problem(s) found')
            ->assertExitCode(1);
        $this->assertSame(0, Portfolio::count());
    }

    public function test_missing_required_column_is_rejected(): void
    {
        $this->artisan('portfolios:import', ['--path' => $this->csv(['1,nap,A,T'], 'portfolio_id,dataset,code,title')])
            ->expectsOutputToContain('missing required column')
            ->assertExitCode(1);
    }

    public function test_prune_removes_rows_absent_from_the_csv(): void
    {
        $this->artisan('portfolios:import', ['--path' => $this->csv($this->good())])->assertExitCode(0);
        $this->artisan('portfolios:import', ['--path' => $this->csv([$this->good()[0]]), '--prune' => true])->assertExitCode(0);
        $this->assertSame([1], Portfolio::pluck('portfolio_id')->all());
    }

    public function test_validate_flags_mismatch_and_writes_report(): void
    {
        $this->artisan('portfolios:import', ['--path' => $this->csv([
            '1,sdg_intervention,O1,SDG 6 overview,S,6,G,F,C,,100,1,',
            '2,sdg_intervention,C1,Child,S,6,G,F,C,,60,0,',
        ])])->assertExitCode(0);

        $this->artisan('portfolios:validate')->assertExitCode(0);
        $report = json_decode(file_get_contents(storage_path('app/dq_report.json')), true);
        $check = collect($report['checks'])->firstWhere('check', 'overview_vs_children');
        $this->assertSame(1, $check['count']);
    }
}
