<?php

namespace Tests\Feature;

use App\Models\Legislature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegislatureSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_hidden_when_there_is_only_one_legislature(): void
    {
        Legislature::factory()->xvii()->withParties()->create();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="legislature-switcher"', false);
    }

    public function test_it_lists_every_legislature_newest_first(): void
    {
        $this->createPreviousAndCurrent();

        $this->get(route('dashboard'))
            ->assertSee('id="legislature-switcher"', false)
            ->assertSeeInOrder([
                __('ui.legislature.name_ongoing', ['sigla' => 'XVII']),
                __('ui.legislature.name', ['sigla' => 'XVI']),
            ]);
    }

    public function test_it_links_to_the_same_page_in_each_legislature(): void
    {
        $this->createPreviousAndCurrent();

        $this->get(route('legislatures.initiatives.index', 'XVI'))
            ->assertSee('value="'.route('initiatives.index').'"', false)
            ->assertSee('value="'.route('legislatures.initiatives.index', 'XVI').'" selected', false);
    }

    public function test_a_party_page_links_to_the_party_list_where_the_party_had_no_group(): void
    {
        $this->createPreviousAndCurrent();

        $this->get(route('parties.show', 'jpp'))
            ->assertOk()
            ->assertSee('value="'.route('legislatures.parties.index', 'XVI').'"', false);

        $this->get(route('parties.show', 'ps'))
            ->assertSee('value="'.route('legislatures.parties.show', ['XVI', 'ps']).'"', false);
    }

    public function test_it_tells_when_the_legislature_shown_has_ended(): void
    {
        $this->createPreviousAndCurrent();
        $notice = __('ui.legislature.closed_notice', ['sigla' => 'XVI', 'start' => '2024', 'end' => '2025']);

        $this->get(route('legislatures.dashboard', 'XVI'))->assertSee($notice);
        $this->get(route('dashboard'))->assertDontSee($notice);
    }

    public function test_it_is_not_shown_on_pages_outside_a_legislature(): void
    {
        $this->createPreviousAndCurrent();

        $this->get(route('about'))->assertDontSee('id="legislature-switcher"', false);
    }

    private function createPreviousAndCurrent(): void
    {
        Legislature::factory()->withParties(['PS', 'PSD'])->create([
            'sigla' => 'XVI',
            'number' => 16,
            'start_date' => '2024-03-26',
            'end_date' => '2025-06-02',
        ]);
        Legislature::factory()->xvii()->withParties()->create();
    }
}
