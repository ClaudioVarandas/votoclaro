<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Vote;
use App\Models\VotePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyAuthoredInitiativesTest extends TestCase
{
    use RefreshDatabase;

    private function createPartyContext(string $party): void
    {
        $initiative = Initiative::factory()->parliamentaryGroup($party)->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id, 'is_latest' => true]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => $party, 'position' => 'favor']);
    }

    public function test_authored_section_displays_on_party_page(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->approved()->create(['title' => 'Authored Initiative PS']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertSee(__('ui.parties.authored_initiatives'));
        $response->assertSee('Authored Initiative PS');
    }

    public function test_empty_state_when_no_authored_initiatives(): void
    {
        $initiative = Initiative::factory()->parliamentaryGroup('PSD')->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id, 'is_latest' => true]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PS', 'position' => 'favor']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertSee(__('ui.parties.authored_empty'));
    }

    public function test_only_shows_initiatives_by_requested_party(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->create(['title' => 'PS Initiative']);
        Initiative::factory()->parliamentaryGroup('PSD')->create(['title' => 'PSD Initiative']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertSee('PS Initiative');
        $response->assertDontSee('PSD Initiative');
    }

    public function test_initiative_title_links_to_detail_page(): void
    {
        $this->createPartyContext('PS');
        $initiative = Initiative::factory()->parliamentaryGroup('PS')->create();

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee(route('initiatives.show', $initiative->id));
    }

    public function test_status_counts_are_displayed(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->approved()->create();
        Initiative::factory()->parliamentaryGroup('PS')->approved()->create();
        Initiative::factory()->parliamentaryGroup('PS')->rejected()->create();
        Initiative::factory()->parliamentaryGroup('PS')->inProgress()->create();

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertViewHas('authoredStatusCounts', [
            'total' => 4,
            'approved' => 2,
            'rejected' => 1,
            'in_progress' => 1,
        ]);
    }

    public function test_filter_by_status_approved(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->approved()->create(['title' => 'Approved Init']);
        Initiative::factory()->parliamentaryGroup('PS')->rejected()->create(['title' => 'Rejected Init']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('Approved Init');
        $response->assertDontSee('Rejected Init');
    }

    public function test_filter_by_status_rejected(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->approved()->create(['title' => 'Approved Init']);
        Initiative::factory()->parliamentaryGroup('PS')->rejected()->create(['title' => 'Rejected Init']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_status' => 'rejected']));

        $response->assertStatus(200);
        $response->assertSee('Rejected Init');
        $response->assertDontSee('Approved Init');
    }

    public function test_filter_by_status_in_progress(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->approved()->create(['title' => 'Approved Init']);
        Initiative::factory()->parliamentaryGroup('PS')->inProgress()->create(['title' => 'InProgress Init']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_status' => 'in_progress']));

        $response->assertStatus(200);
        $response->assertSee('InProgress Init');
        $response->assertDontSee('Approved Init');
    }

    public function test_filter_by_initiative_type(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->create([
            'title' => 'Projeto de Lei ABC',
            'initiative_type' => 'pjl',
            'initiative_type_label' => 'Projeto de Lei',
        ]);
        Initiative::factory()->parliamentaryGroup('PS')->create([
            'title' => 'Proposta de Resolução XYZ',
            'initiative_type' => 'ppr',
            'initiative_type_label' => 'Proposta de Resolução',
        ]);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_type' => 'pjl']));

        $response->assertStatus(200);
        $response->assertSee('Projeto de Lei ABC');
        $response->assertDontSee('Proposta de Resolução XYZ');
    }

    public function test_search_filters_by_initiative_title(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->create(['title' => 'Orçamento de Estado 2026']);
        Initiative::factory()->parliamentaryGroup('PS')->create(['title' => 'Alteração ao Código Penal']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_search' => 'Orçamento']));

        $response->assertStatus(200);
        $response->assertSee('Orçamento de Estado 2026');
        $response->assertDontSee('Alteração ao Código Penal');
    }

    public function test_pagination_limits_to_20_per_page(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->count(25)->create();

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertViewHas('authoredInitiatives', fn ($initiatives) => $initiatives->count() === 20);
    }

    public function test_pagination_page_2_returns_remaining(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->count(25)->create();

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_page' => 2]));

        $response->assertStatus(200);
        $response->assertViewHas('authoredInitiatives', fn ($initiatives) => $initiatives->count() === 5);
    }

    public function test_authored_page_is_independent_from_voting_history_page(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->count(25)->create();

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_page' => 2, 'page' => 1]));

        $response->assertStatus(200);
        $response->assertViewHas('authoredInitiatives', fn ($initiatives) => $initiatives->count() === 5);
        $response->assertViewHas('votes', fn ($votes) => $votes->currentPage() === 1);
    }

    public function test_invalid_authored_status_returns_validation_error(): void
    {
        $this->createPartyContext('PS');

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_status' => 'invalid']));

        $response->assertStatus(302);
    }

    public function test_sort_by_date_ascending(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->create(['title' => 'January Init', 'entry_date' => '2026-01-01']);
        Initiative::factory()->parliamentaryGroup('PS')->create(['title' => 'June Init', 'entry_date' => '2026-06-01']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'authored_sort' => 'date', 'authored_direction' => 'asc']));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['January Init', 'June Init']);
    }

    public function test_default_sort_is_date_descending(): void
    {
        $this->createPartyContext('PS');
        Initiative::factory()->parliamentaryGroup('PS')->create(['title' => 'January Init', 'entry_date' => '2026-01-01']);
        Initiative::factory()->parliamentaryGroup('PS')->create(['title' => 'June Init', 'entry_date' => '2026-06-01']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['June Init', 'January Init']);
    }
}
