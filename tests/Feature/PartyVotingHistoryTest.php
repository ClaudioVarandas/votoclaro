<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Vote;
use App\Models\VotePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyVotingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function createVoteForParty(string $party, string $position = 'favor', array $voteOverrides = [], array $initiativeOverrides = []): VotePosition
    {
        $initiative = Initiative::factory()->create($initiativeOverrides);
        $vote = Vote::factory()->create(array_merge(['initiative_id' => $initiative->id, 'is_latest' => true], $voteOverrides));

        return VotePosition::factory()->create([
            'vote_id' => $vote->id,
            'party' => $party,
            'position' => $position,
        ]);
    }

    public function test_voting_history_displays_on_party_page(): void
    {
        $this->createVoteForParty('PS', 'favor');

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertSee(__('ui.parties.voting_history'));
    }

    public function test_voting_history_shows_empty_state_when_no_votes(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id, 'is_latest' => true]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PS', 'position' => 'favor']);

        $initiative2 = Initiative::factory()->create();
        $vote2 = Vote::factory()->create(['initiative_id' => $initiative2->id, 'is_latest' => true]);
        VotePosition::factory()->create(['vote_id' => $vote2->id, 'party' => 'BE', 'position' => 'favor']);

        $response = $this->get(route('parties.show', 'be'));

        $response->assertStatus(200);
    }

    public function test_voting_history_shows_initiative_title(): void
    {
        $vp = $this->createVoteForParty('PS', 'favor', [], ['title' => 'Orçamento de Estado 2026']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee('Orçamento de Estado 2026');
    }

    public function test_voting_history_links_to_initiative_page(): void
    {
        $vp = $this->createVoteForParty('PS', 'favor');
        $initiativeId = $vp->vote->initiative->id;

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee(route('initiatives.show', $initiativeId));
    }

    public function test_only_latest_votes_are_shown(): void
    {
        $initiative = Initiative::factory()->create(['title' => 'Visible Initiative']);
        Vote::factory()->create(['initiative_id' => $initiative->id, 'is_latest' => false]);
        $latestVote = Vote::factory()->create(['initiative_id' => $initiative->id, 'is_latest' => true]);

        VotePosition::factory()->create(['vote_id' => $latestVote->id, 'party' => 'PS', 'position' => 'favor']);

        $initiative2 = Initiative::factory()->create(['title' => 'Old Vote Initiative']);
        $oldVote = Vote::factory()->create(['initiative_id' => $initiative2->id, 'is_latest' => false]);
        VotePosition::factory()->create(['vote_id' => $oldVote->id, 'party' => 'PS', 'position' => 'contra']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee('Visible Initiative');
        $response->assertDontSee('Old Vote Initiative');
    }

    public function test_filter_by_position_favor(): void
    {
        $this->createVoteForParty('PS', 'favor', ['result' => 'Aprovado'], ['title' => 'Favor Initiative']);
        $this->createVoteForParty('PS', 'contra', ['result' => 'Rejeitado'], ['title' => 'Contra Initiative']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'position' => 'favor']));

        $response->assertStatus(200);
        $response->assertSee('Favor Initiative');
        $response->assertDontSee('Contra Initiative');
    }

    public function test_filter_by_position_contra(): void
    {
        $this->createVoteForParty('PS', 'favor', ['result' => 'Aprovado'], ['title' => 'Favor Initiative']);
        $this->createVoteForParty('PS', 'contra', ['result' => 'Rejeitado'], ['title' => 'Contra Initiative']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'position' => 'contra']));

        $response->assertStatus(200);
        $response->assertSee('Contra Initiative');
        $response->assertDontSee('Favor Initiative');
    }

    public function test_filter_by_position_abstencao(): void
    {
        $this->createVoteForParty('PS', 'favor', [], ['title' => 'Favor Initiative']);
        $this->createVoteForParty('PS', 'abstencao', [], ['title' => 'Abstencao Initiative']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'position' => 'abstencao']));

        $response->assertStatus(200);
        $response->assertSee('Abstencao Initiative');
        $response->assertDontSee('Favor Initiative');
    }

    public function test_position_counts_are_displayed(): void
    {
        $this->createVoteForParty('PS', 'favor');
        $this->createVoteForParty('PS', 'favor');
        $this->createVoteForParty('PS', 'contra');

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee(__('ui.parties.filter_all').' (3)');
        $response->assertSee(__('ui.position.favor').' (2)');
        $response->assertSee(__('ui.position.contra').' (1)');
        $response->assertSee(__('ui.position.abstencao').' (0)');
    }

    public function test_invalid_position_filter_returns_validation_error(): void
    {
        $this->createVoteForParty('PS');

        $response = $this->get(route('parties.show', ['party' => 'ps', 'position' => 'invalid']));

        $response->assertStatus(302);
    }

    public function test_invalid_sort_returns_validation_error(): void
    {
        $this->createVoteForParty('PS');

        $response = $this->get(route('parties.show', ['party' => 'ps', 'sort' => 'title']));

        $response->assertStatus(302);
    }

    public function test_invalid_direction_returns_validation_error(): void
    {
        $this->createVoteForParty('PS');

        $response = $this->get(route('parties.show', ['party' => 'ps', 'direction' => 'sideways']));

        $response->assertStatus(302);
    }

    public function test_sort_by_date_ascending(): void
    {
        $this->createVoteForParty('PS', 'favor', ['date' => '2026-01-01'], ['title' => 'January Init', 'final_vote_date' => '2026-01-01']);
        $this->createVoteForParty('PS', 'favor', ['date' => '2026-06-01'], ['title' => 'June Init', 'final_vote_date' => '2026-06-01']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'sort' => 'date', 'direction' => 'asc']));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['January Init', 'June Init']);
    }

    public function test_sort_by_date_descending_is_default(): void
    {
        $this->createVoteForParty('PS', 'favor', ['date' => '2026-01-01'], ['title' => 'January Init', 'final_vote_date' => '2026-01-01']);
        $this->createVoteForParty('PS', 'favor', ['date' => '2026-06-01'], ['title' => 'June Init', 'final_vote_date' => '2026-06-01']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['June Init', 'January Init']);
    }

    public function test_pagination_limits_results_to_20(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->createVoteForParty('PS', 'favor');
        }

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertViewHas('votes', fn ($votes) => $votes->count() === 20);
    }

    public function test_pagination_page_2_returns_remaining(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->createVoteForParty('PS', 'favor');
        }

        $response = $this->get(route('parties.show', ['party' => 'ps', 'page' => 2]));

        $response->assertStatus(200);
        $response->assertViewHas('votes', fn ($votes) => $votes->count() === 5);
    }

    public function test_pendente_shown_when_final_vote_date_is_null(): void
    {
        $this->createVoteForParty('PS', 'favor', [], ['final_vote_date' => null, 'status' => 'in_progress']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee(__('ui.parties.date_pending'));
    }

    public function test_vote_result_badge_shows_prejudicado(): void
    {
        $this->createVoteForParty('PS', 'favor', ['result' => 'Prejudicado']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee(__('ui.vote_result.Prejudicado'));
    }

    public function test_404_returned_before_querying_votes_for_invalid_party(): void
    {
        $response = $this->get(route('parties.show', 'nonexistent'));

        $response->assertStatus(404);
    }

    public function test_filter_by_initiative_type(): void
    {
        $this->createVoteForParty('PS', 'favor', [], [
            'title' => 'Projeto de Lei ABC',
            'initiative_type' => 'J',
            'initiative_type_label' => 'Projeto de Lei',
        ]);
        $this->createVoteForParty('PS', 'favor', [], [
            'title' => 'Proposta de Resolução XYZ',
            'initiative_type' => 'S',
            'initiative_type_label' => 'Proposta de Resolução',
        ]);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'type' => 'J']));

        $response->assertStatus(200);
        $response->assertSee('Projeto de Lei ABC');
        $response->assertDontSee('Proposta de Resolução XYZ');
    }

    public function test_type_filter_dropdown_is_displayed(): void
    {
        $this->createVoteForParty('PS', 'favor', [], [
            'initiative_type' => 'J',
            'initiative_type_label' => 'Projeto de Lei',
        ]);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee(__('ui.parties.all_types'));
        $response->assertSee('Projeto de Lei');
    }

    public function test_type_filter_updates_position_counts(): void
    {
        $this->createVoteForParty('PS', 'favor', [], ['initiative_type' => 'J', 'initiative_type_label' => 'Projeto de Lei']);
        $this->createVoteForParty('PS', 'contra', [], ['initiative_type' => 'J', 'initiative_type_label' => 'Projeto de Lei']);
        $this->createVoteForParty('PS', 'favor', [], ['initiative_type' => 'S', 'initiative_type_label' => 'Proposta de Resolução']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'type' => 'J']));

        $response->assertSee(__('ui.parties.filter_all').' (2)');
        $response->assertSee(__('ui.position.favor').' (1)');
        $response->assertSee(__('ui.position.contra').' (1)');
    }

    public function test_search_filters_by_initiative_title(): void
    {
        $this->createVoteForParty('PS', 'favor', [], ['title' => 'Orçamento de Estado 2026']);
        $this->createVoteForParty('PS', 'favor', [], ['title' => 'Alteração ao Código Penal']);

        $response = $this->get(route('parties.show', ['party' => 'ps', 'search' => 'Orçamento']));

        $response->assertStatus(200);
        $response->assertSee('Orçamento de Estado 2026');
        $response->assertDontSee('Alteração ao Código Penal');
    }

    public function test_search_input_preserves_value(): void
    {
        $this->createVoteForParty('PS', 'favor');

        $response = $this->get(route('parties.show', ['party' => 'ps', 'search' => 'test query']));

        $response->assertStatus(200);
        $response->assertViewHas('currentSearch', 'test query');
    }
}
