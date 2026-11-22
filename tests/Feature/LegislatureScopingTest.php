<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Legislature;
use App\Models\Vote;
use App\Models\VotePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegislatureScopingTest extends TestCase
{
    use RefreshDatabase;

    private Legislature $previous;

    private Legislature $current;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previous = Legislature::factory()->withParties(['PS', 'PSD', 'PEV'])->create([
            'sigla' => 'XVI',
            'number' => 16,
            'start_date' => '2024-03-26',
            'end_date' => '2025-06-02',
        ]);
        $this->current = Legislature::factory()->xvii()->withParties()->create();
    }

    public function test_unprefixed_pages_show_the_current_legislature(): void
    {
        $this->initiativeIn($this->previous, 'Lei da legislatura anterior');
        $this->initiativeIn($this->current, 'Lei da legislatura atual');

        $this->get(route('initiatives.index'))
            ->assertOk()
            ->assertSee('Lei da legislatura atual')
            ->assertDontSee('Lei da legislatura anterior');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lei da legislatura atual')
            ->assertDontSee('Lei da legislatura anterior');
    }

    public function test_prefixed_pages_show_that_legislature(): void
    {
        $this->initiativeIn($this->previous, 'Lei da legislatura anterior');
        $this->initiativeIn($this->current, 'Lei da legislatura atual');

        $this->get(route('legislatures.initiatives.index', 'XVI'))
            ->assertOk()
            ->assertSee('Lei da legislatura anterior')
            ->assertDontSee('Lei da legislatura atual');

        $this->get(route('legislatures.dashboard', 'XVI'))
            ->assertOk()
            ->assertSee('Lei da legislatura anterior')
            ->assertDontSee('Lei da legislatura atual');
    }

    public function test_the_legislature_sigla_is_case_insensitive(): void
    {
        $this->get('/legislatura/xvi/iniciativas')->assertOk();
    }

    public function test_an_unknown_legislature_returns_404(): void
    {
        $this->get('/legislatura/XV/iniciativas')->assertNotFound();
    }

    public function test_a_non_roman_legislature_segment_returns_404(): void
    {
        $this->get('/legislatura/17/iniciativas')->assertNotFound();
    }

    public function test_links_on_a_previous_legislature_page_stay_in_that_legislature(): void
    {
        $this->get(route('legislatures.dashboard', 'XVI'))
            ->assertSee(route('legislatures.initiatives.index', 'XVI'))
            ->assertSee(route('legislatures.parties.index', 'XVI'))
            ->assertSee(route('legislatures.parties.show', ['XVI', 'pev']));
    }

    public function test_links_on_a_current_legislature_page_are_unprefixed(): void
    {
        $this->get(route('dashboard'))
            ->assertSee(route('parties.show', 'ps'))
            ->assertDontSee('/legislatura/XVII');
    }

    public function test_party_list_shows_the_parties_of_that_legislature(): void
    {
        $this->get(route('legislatures.parties.index', 'XVI'))
            ->assertOk()
            ->assertViewHas('partyStats', fn (array $stats): bool => array_keys($stats) === ['PEV', 'PS', 'PSD']);

        $this->get(route('parties.index'))
            ->assertViewHas('partyStats', fn (array $stats): bool => ! array_key_exists('PEV', $stats) && array_key_exists('JPP', $stats));
    }

    public function test_party_page_counts_only_votes_of_that_legislature(): void
    {
        $this->voteIn($this->previous, 'PS', 'contra');
        $this->voteIn($this->current, 'PS', 'favor');
        $this->voteIn($this->current, 'PS', 'favor');

        $this->get(route('parties.show', 'ps'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['total_votes'] === 2 && $stats['contra'] === 0);

        $this->get(route('legislatures.parties.show', ['XVI', 'ps']))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['total_votes'] === 1 && $stats['contra'] === 1);
    }

    public function test_party_page_returns_404_for_a_party_absent_from_that_legislature(): void
    {
        $this->get(route('legislatures.parties.show', ['XVI', 'jpp']))->assertNotFound();
        $this->get(route('parties.show', 'pev'))->assertNotFound();
    }

    public function test_party_authored_initiatives_are_limited_to_that_legislature(): void
    {
        Initiative::factory()->parliamentaryGroup('PS')->create(['legislature_id' => $this->previous->id, 'title' => 'Projeto PS anterior']);
        Initiative::factory()->parliamentaryGroup('PS')->create(['legislature_id' => $this->current->id, 'title' => 'Projeto PS atual']);

        $this->get(route('parties.show', 'ps'))
            ->assertSee('Projeto PS atual')
            ->assertDontSee('Projeto PS anterior');
    }

    public function test_dashboard_stats_count_only_that_legislature(): void
    {
        Initiative::factory()->approved()->count(2)->create(['legislature_id' => $this->previous->id]);
        Initiative::factory()->approved()->count(3)->create(['legislature_id' => $this->current->id]);

        $this->get(route('dashboard'))->assertViewHas('totalInitiatives', 3);
        $this->get(route('legislatures.dashboard', 'XVI'))->assertViewHas('totalInitiatives', 2);
    }

    public function test_initiative_page_names_its_legislature_and_links_to_it(): void
    {
        $initiative = $this->initiativeIn($this->previous, 'Lei da legislatura anterior');

        $this->get(route('initiatives.show', $initiative))
            ->assertOk()
            ->assertSee(__('ui.legislature.name', ['sigla' => 'XVI']))
            ->assertSee(route('legislatures.initiatives.index', 'XVI'));
    }

    public function test_initiative_page_of_the_current_legislature_links_unprefixed(): void
    {
        $initiative = $this->initiativeIn($this->current, 'Lei da legislatura atual');

        $this->get(route('initiatives.show', $initiative))
            ->assertSee(route('initiatives.index'))
            ->assertDontSee('/legislatura/');
    }

    public function test_sitemap_lists_the_parties_of_the_current_legislature(): void
    {
        $this->get(route('sitemap'))
            ->assertSee(route('parties.show', 'jpp'))
            ->assertDontSee(route('parties.show', 'pev'));
    }

    private function initiativeIn(Legislature $legislature, string $title): Initiative
    {
        return Initiative::factory()->approved()->create([
            'legislature_id' => $legislature->id,
            'title' => $title,
        ]);
    }

    private function voteIn(Legislature $legislature, string $party, string $position): void
    {
        $initiative = Initiative::factory()->create(['legislature_id' => $legislature->id]);
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id, 'is_latest' => true]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => $party, 'position' => $position]);
    }
}
