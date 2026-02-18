<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Vote;
use App\Models\VotePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_successful_response(): void
    {
        $response = $this->get(route('parties.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.parties.index');
    }

    public function test_index_displays_party_cards(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PS', 'position' => 'favor']);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PSD', 'position' => 'favor']);

        $response = $this->get(route('parties.index'));

        $response->assertSee('PS');
        $response->assertSee('PSD');
    }

    public function test_index_excludes_deputy_names(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'Pedro Nuno Santos (PS)', 'position' => 'favor']);

        $response = $this->get(route('parties.index'));

        $response->assertDontSee('Pedro Nuno Santos');
    }

    public function test_index_displays_all_main_parties(): void
    {
        $response = $this->get(route('parties.index'));

        $response->assertViewHas('partyStats');
        $this->assertCount(10, $response->viewData('partyStats'));
    }
}
