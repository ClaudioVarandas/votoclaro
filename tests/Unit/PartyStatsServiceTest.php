<?php

namespace Tests\Unit;

use App\Models\Initiative;
use App\Models\Legislature;
use App\Models\Vote;
use App\Models\VotePosition;
use App\Services\PartyStatsService;
use Database\Factories\LegislatureFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PartyStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PartyStatsService $service;

    private Legislature $legislature;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PartyStatsService::class);
        $this->legislature = Legislature::factory()->xvii()->withParties()->create();
        Cache::flush();
    }

    public function test_get_all_party_stats_returns_all_main_parties(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PS', 'position' => 'favor']);

        $stats = $this->service->getAllPartyStats($this->legislature);

        $this->assertCount(10, $stats);
        foreach (LegislatureFactory::XVII_PARTIES as $party) {
            $this->assertArrayHasKey($party, $stats);
        }
    }

    public function test_counts_favor_contra_abstencao_correctly(): void
    {
        $initiative = Initiative::factory()->create();

        $vote1 = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote1->id, 'party' => 'PS', 'position' => 'favor']);

        $vote2 = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote2->id, 'party' => 'PS', 'position' => 'favor']);

        $vote3 = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote3->id, 'party' => 'PS', 'position' => 'abstencao']);

        $stats = $this->service->getPartyStats($this->legislature, 'PS');

        $this->assertEquals(3, $stats['total_votes']);
        $this->assertEquals(2, $stats['favor']);
        $this->assertEquals(0, $stats['contra']);
        $this->assertEquals(1, $stats['abstencao']);
    }

    public function test_counts_contra_correctly(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'BE', 'position' => 'contra']);

        $stats = $this->service->getPartyStats($this->legislature, 'BE');

        $this->assertEquals(1, $stats['total_votes']);
        $this->assertEquals(0, $stats['favor']);
        $this->assertEquals(1, $stats['contra']);
    }

    public function test_excludes_non_main_parties(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'XPTO', 'position' => 'favor']);

        $stats = $this->service->getPartyStats($this->legislature, 'XPTO');

        $this->assertNull($stats);
    }

    public function test_excludes_numbered_entries(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => '13-PS', 'position' => 'favor']);

        $stats = $this->service->getPartyStats($this->legislature, 'PS');

        $this->assertEquals(0, $stats['total_votes']);
    }

    public function test_government_alignment_calculation(): void
    {
        $govInitiative = Initiative::factory()->government()->create();
        $otherInitiative = Initiative::factory()->parliamentaryGroup('BE')->create();

        $govVote = Vote::factory()->create(['initiative_id' => $govInitiative->id]);
        VotePosition::factory()->create(['vote_id' => $govVote->id, 'party' => 'PS', 'position' => 'favor']);

        $otherVote = Vote::factory()->create(['initiative_id' => $otherInitiative->id]);
        VotePosition::factory()->create(['vote_id' => $otherVote->id, 'party' => 'PS', 'position' => 'favor']);

        $stats = $this->service->getPartyStats($this->legislature, 'PS');

        // PS voted favor on 1 government vote out of 1 government vote = 100%
        $this->assertEquals(100.0, $stats['government_alignment']);
    }

    public function test_government_alignment_partial(): void
    {
        $govInitiative1 = Initiative::factory()->government()->create();
        $govInitiative2 = Initiative::factory()->government()->create();

        $govVote1 = Vote::factory()->create(['initiative_id' => $govInitiative1->id]);
        VotePosition::factory()->create(['vote_id' => $govVote1->id, 'party' => 'PSD', 'position' => 'favor']);

        $govVote2 = Vote::factory()->create(['initiative_id' => $govInitiative2->id]);
        VotePosition::factory()->create(['vote_id' => $govVote2->id, 'party' => 'PSD', 'position' => 'contra']);

        $stats = $this->service->getPartyStats($this->legislature, 'PSD');

        // PSD: favor on vote1 (gov), contra on vote2 (gov) = 1/2 = 50%
        $this->assertEquals(50.0, $stats['government_alignment']);
    }

    public function test_monthly_trend_sorted_chronologically(): void
    {
        $initiative = Initiative::factory()->create();

        $vote1 = Vote::factory()->create(['initiative_id' => $initiative->id, 'date' => '2025-03-15', 'is_latest' => true]);
        VotePosition::factory()->create(['vote_id' => $vote1->id, 'party' => 'PS', 'position' => 'favor']);

        $vote2 = Vote::factory()->create(['initiative_id' => $initiative->id, 'date' => '2025-01-10', 'is_latest' => true]);
        VotePosition::factory()->create(['vote_id' => $vote2->id, 'party' => 'PS', 'position' => 'favor']);

        $vote3 = Vote::factory()->create(['initiative_id' => $initiative->id, 'date' => '2025-02-20', 'is_latest' => true]);
        VotePosition::factory()->create(['vote_id' => $vote3->id, 'party' => 'PS', 'position' => 'contra']);

        $trend = $this->service->getMonthlyTrend($this->legislature, 'PS');

        $this->assertCount(3, $trend);
        $this->assertEquals('2025-01', $trend[0]['month']);
        $this->assertEquals('2025-02', $trend[1]['month']);
        $this->assertEquals('2025-03', $trend[2]['month']);
    }

    public function test_returns_null_for_unknown_party(): void
    {
        $this->assertNull($this->service->getPartyStats($this->legislature, 'XYZ'));
    }

    public function test_party_acronym_is_case_insensitive(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PS', 'position' => 'favor']);

        $stats = $this->service->getPartyStats($this->legislature, 'ps');

        $this->assertNotNull($stats);
        $this->assertEquals('PS', $stats['acronym']);
    }

    public function test_percentages_are_correct(): void
    {
        $initiative = Initiative::factory()->create();

        $vote1 = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote1->id, 'party' => 'IL', 'position' => 'favor']);

        $vote2 = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote2->id, 'party' => 'IL', 'position' => 'contra']);

        $vote3 = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote3->id, 'party' => 'IL', 'position' => 'abstencao']);

        $stats = $this->service->getPartyStats($this->legislature, 'IL');

        // IL: 1 favor, 1 contra, 1 abstencao = 3 total
        $this->assertEquals(33.3, $stats['favor_pct']);
        $this->assertEquals(33.3, $stats['contra_pct']);
        $this->assertEquals(33.3, $stats['abstencao_pct']);
    }
}
