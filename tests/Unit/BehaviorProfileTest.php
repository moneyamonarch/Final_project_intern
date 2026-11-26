<?php

namespace Tests\Unit;

use App\Services\InvestorProfiler;
use Tests\TestCase;

class BehaviorProfileTest extends TestCase
{
    public function test_scores_map_to_0_100_and_skipped_is_null(): void
    {
        $b = (new InvestorProfiler())->behavior(['loss_sting' => 5, 'patience' => '1', 'herding' => 1]);
        $this->assertSame(100, $b['loss_aversion']);
        $this->assertSame(100, $b['time_preference']);
        $this->assertSame(0, $b['herding']);
        $this->assertNull($b['overconfidence']);
        $this->assertSame(3, $b['answered']);
        $this->assertSame([], (new InvestorProfiler())->behavior([])['notes']);
    }

    public function test_overconfidence_needs_a_wrong_answer_and_high_confidence(): void
    {
        $p = new InvestorProfiler();
        $this->assertSame(100, $p->behavior(['calib_fact' => 'c', 'calib_conf' => 5])['overconfidence']);
        $this->assertSame(0, $p->behavior(['calib_fact' => 'b', 'calib_conf' => 5])['overconfidence']);
        $this->assertNull($p->behavior(['calib_fact' => 'c'])['overconfidence']);
        $this->assertCount(1, $p->behavior(['calib_fact' => 'a', 'calib_conf' => 5])['notes']);
    }

    public function test_loss_aversion_or_conservative_risk_lowers_lambda(): void
    {
        $p = new InvestorProfiler();
        $this->assertSame(0.7, $p->lambda(['risk' => ['band' => 'Balanced'], 'behavior' => ['loss_aversion' => 50]]));
        $this->assertSame(0.5, $p->lambda(['risk' => ['band' => 'Balanced'], 'behavior' => ['loss_aversion' => 100]]));
        $this->assertSame(0.5, $p->lambda(['risk' => ['band' => 'Conservative'], 'behavior' => ['loss_aversion' => null]]));
        $this->assertSame(0.7, $p->lambda(['risk' => null, 'behavior' => ['loss_aversion' => null]]));
    }

    public function test_build_includes_behavior_and_counts_answers(): void
    {
        $profile = (new InvestorProfiler())->build(['loss_sting' => 5, 'herding' => 5], config('questionnaire'));
        $this->assertSame(100, $profile['behavior']['loss_aversion']);
        $this->assertSame(2, $profile['answered']);
    }
}
