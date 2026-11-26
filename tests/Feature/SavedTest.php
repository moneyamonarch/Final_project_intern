<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedTest extends TestCase
{
    use RefreshDatabase;

    private function make(int $id): void
    {
        Portfolio::query()->create(['portfolio_id' => $id, 'dataset' => 'sdg_intervention', 'code' => "C$id", 'title' => "Title $id",
            'sdg_num' => 1, 'focus_group' => 'G', 'focus_area' => 'F', 'category' => 'C', 'total' => 10, 'is_overview' => false]);
    }

    public function test_batch_save_list_and_remove(): void
    {
        $this->make(1); $this->make(2); $this->make(3);
        $this->post('/saved', ['save' => [1, 2, 3]])->assertRedirect(route('saved.index'));
        $this->get('/saved')->assertOk()->assertSee('Title 1')->assertSee('Title 2')->assertSee('Title 3');
        $this->delete('/saved', ['remove' => [2]])->assertRedirect();
        $this->get('/saved')->assertDontSee('Title 2')->assertSee('Title 3');
        $this->delete('/saved', ['all' => 1]);
        $this->get('/saved')->assertSee('Nothing saved yet');
    }

    public function test_empty_or_unknown_selection_is_rejected(): void
    {
        $this->post('/saved', [])->assertSessionHasErrors('save');
        $this->post('/saved', ['save' => [999]])->assertSessionHasErrors('save.0');
    }
}
