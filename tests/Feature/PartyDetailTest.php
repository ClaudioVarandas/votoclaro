<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Vote;
use App\Models\VotePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_successful_response(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PS', 'position' => 'favor']);

        $response = $this->get(route('parties.show', 'ps'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.parties.show');
    }

    public function test_show_displays_party_stats(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'BE', 'position' => 'favor']);

        $response = $this->get(route('parties.show', 'be'));

        $response->assertSee('BE');
        $response->assertSee(__('ui.parties.total_votes'));
    }

    public function test_show_displays_government_alignment(): void
    {
        $govInitiative = Initiative::factory()->government()->create();
        $vote = Vote::factory()->create(['initiative_id' => $govInitiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PSD', 'position' => 'favor']);

        $response = $this->get(route('parties.show', 'psd'));

        $response->assertSee(__('ui.parties.government_alignment'));
    }

    public function test_show_returns_404_for_invalid_party(): void
    {
        $response = $this->get(route('parties.show', 'xyz'));

        $response->assertStatus(404);
    }

    public function test_show_is_case_insensitive(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'IL', 'position' => 'favor']);

        $response = $this->get(route('parties.show', 'il'));
        $response->assertStatus(200);

        $response = $this->get(route('parties.show', 'IL'));
        $response->assertStatus(200);
    }

    public function test_show_works_with_hyphenated_party(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'CDS-PP', 'position' => 'favor']);

        $response = $this->get(route('parties.show', 'cds-pp'));

        $response->assertStatus(200);
        $response->assertSee('CDS-PP');
    }

    public function test_show_has_back_link(): void
    {
        $response = $this->get(route('parties.show', 'ps'));

        $response->assertSee(__('ui.parties.back_to_list'));
        $response->assertSee(route('parties.index'));
    }
}
