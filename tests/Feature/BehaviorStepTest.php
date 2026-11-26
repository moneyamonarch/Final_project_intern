<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BehaviorStepTest extends TestCase
{
    use RefreshDatabase;

    public function test_behavior_step_renders_validates_stores_and_shows_notes_on_results(): void
    {
        Portfolio::create(['portfolio_id' => 1, 'dataset' => 'sdg_intervention', 'code' => 'X1', 'title' => 'Rural Water', 'summary' => 's',
            'sdg_num' => 6, 'focus_group' => 'Development', 'focus_area' => 'Water', 'category' => 'Water', 'total' => 5, 'is_overview' => false]);

        $this->get('/questionnaire/4')->assertOk()->assertSee('How do you make funding decisions?')->assertSee('How confident are you in that answer?');
        $this->post('/questionnaire/4', ['loss_sting' => '9'])->assertSessionHasErrors('loss_sting');
        $this->post('/questionnaire/4', ['loss_sting' => '5', 'herding' => '5', 'calib_fact' => 'c', 'calib_conf' => '5'])->assertRedirect('/questionnaire/5');
        $this->assertSame('5', session('questionnaire.profile.loss_sting'));

        $this->post('/questionnaire/5', ['sdgs' => [6]]);
        foreach ([6, 7, 8] as $s) {
            $this->post("/questionnaire/{$s}", ['_skip' => '1']);
        }
        $this->get('/recommendations')->assertOk()->assertSee('spread more widely')->assertSee('ignores popularity')->assertSee('confidence was higher than');
    }
}
