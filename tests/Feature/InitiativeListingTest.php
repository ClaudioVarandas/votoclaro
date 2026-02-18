<?php

namespace Tests\Feature;

use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_successful_response(): void
    {
        Initiative::factory()->count(3)->create();

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.initiatives.index');
    }

    public function test_index_displays_initiatives(): void
    {
        $initiative = Initiative::factory()->create(['title' => 'Proposta de Lei sobre Habitação']);

        $response = $this->get(route('initiatives.index'));

        $response->assertSee('Proposta de Lei sobre Habitação');
    }

    public function test_index_filters_by_status(): void
    {
        Initiative::factory()->approved()->create(['title' => 'Aprovada Initiative']);
        Initiative::factory()->rejected()->create(['title' => 'Rejeitada Initiative']);

        $response = $this->get(route('initiatives.index', ['status' => 'approved']));

        $response->assertSee('Aprovada Initiative');
        $response->assertDontSee('Rejeitada Initiative');
    }

    public function test_index_filters_by_author_category(): void
    {
        Initiative::factory()->government()->create(['title' => 'Gov Initiative']);
        Initiative::factory()->parliamentaryGroup('PS')->create(['title' => 'Parliament Initiative']);

        $response = $this->get(route('initiatives.index', ['author_category' => 'government']));

        $response->assertSee('Gov Initiative');
        $response->assertDontSee('Parliament Initiative');
    }

    public function test_index_filters_by_search(): void
    {
        Initiative::factory()->create(['title' => 'Proposta de Lei sobre Habitação']);
        Initiative::factory()->create(['title' => 'Projeto de Resolução sobre Saúde']);

        $response = $this->get(route('initiatives.index', ['search' => 'Habitação']));

        $response->assertSee('Proposta de Lei sobre Habitação');
        $response->assertDontSee('Projeto de Resolução sobre Saúde');
    }

    public function test_index_paginates_results(): void
    {
        Initiative::factory()->count(25)->create();

        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $response->assertViewHas('initiatives');
        $this->assertCount(20, $response->viewData('initiatives'));
    }

    public function test_index_ajax_returns_partial(): void
    {
        Initiative::factory()->count(5)->create();

        $response = $this->get(route('initiatives.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('X-Has-More-Pages');
    }

    public function test_index_empty_state(): void
    {
        $response = $this->get(route('initiatives.index'));

        $response->assertStatus(200);
        $response->assertSee(__('ui.initiatives.no_results'));
    }
}
