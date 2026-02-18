<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Vote;
use App\Models\VotePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_successful_response(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.dashboard');
    }

    public function test_dashboard_displays_primary_metric_cards(): void
    {
        Initiative::factory()->approved()->count(3)->create();
        Initiative::factory()->rejected()->count(2)->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.total_initiatives'));
        $response->assertSee(__('ui.dashboard.approval_rate'));
        $response->assertSee(__('ui.dashboard.government_initiatives'));
    }

    public function test_dashboard_approval_rate_card_includes_unanimous_count(): void
    {
        $initiative = Initiative::factory()->approved()->create();
        Vote::factory()->create([
            'initiative_id' => $initiative->id,
            'unanimous' => true,
            'is_latest' => true,
        ]);
        Vote::factory()->create([
            'initiative_id' => $initiative->id,
            'unanimous' => false,
            'is_latest' => true,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.unanimous_votes'));
        $response->assertViewHas('unanimousCount', 1);
        $response->assertViewHas('unanimousPct', 50.0);
    }

    public function test_dashboard_displays_most_active_party_leaderboard(): void
    {
        Initiative::factory()->parliamentaryGroup('PS')->count(15)->create();
        Initiative::factory()->parliamentaryGroup('PSD')->count(10)->create();
        Initiative::factory()->parliamentaryGroup('CH')->count(5)->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.most_active_party'));
        $response->assertViewHas('mostActiveParties', function ($parties) {
            return $parties->count() === 3
                && $parties->first()->author_party === 'PS'
                && $parties->first()->total === 15;
        });
    }

    public function test_dashboard_displays_highest_approval_rate_leaderboard(): void
    {
        Initiative::factory()->parliamentaryGroup('PS')->approved()->count(8)->create();
        Initiative::factory()->parliamentaryGroup('PS')->rejected()->count(2)->create();
        Initiative::factory()->parliamentaryGroup('PSD')->approved()->count(6)->create();
        Initiative::factory()->parliamentaryGroup('PSD')->rejected()->count(4)->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.highest_approval'));
        $response->assertViewHas('highestApprovalParties', function ($parties) {
            return $parties->count() === 2
                && $parties->first()->author_party === 'PS'
                && $parties->first()->approval_rate === 80.0;
        });
    }

    public function test_highest_approval_excludes_parties_below_threshold(): void
    {
        Initiative::factory()->parliamentaryGroup('IL')->approved()->count(5)->create();

        $response = $this->get(route('dashboard'));

        $response->assertViewHas('highestApprovalParties', function ($parties) {
            return $parties->where('author_party', 'IL')->isEmpty();
        });
    }

    public function test_dashboard_initiatives_card_shows_government_and_parliament(): void
    {
        Initiative::factory()->government()->count(4)->create();
        Initiative::factory()->parliamentaryGroup('PS')->count(6)->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.government_initiatives'));
        $response->assertSee(__('ui.government'));
        $response->assertSee(__('ui.author_type.Other'));
    }

    public function test_dashboard_displays_least_active_party_leaderboard(): void
    {
        Initiative::factory()->parliamentaryGroup('PS')->count(15)->create();
        Initiative::factory()->parliamentaryGroup('PSD')->count(10)->create();
        Initiative::factory()->parliamentaryGroup('CH')->count(5)->create();
        Initiative::factory()->parliamentaryGroup('IL')->count(3)->create();

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.least_active_party'));
        $response->assertViewHas('leastActiveParties', function ($parties) {
            return $parties->count() === 3
                && $parties->first()->author_party === 'IL'
                && $parties->first()->total === 3;
        });
    }

    public function test_dashboard_displays_party_quick_glance_grid(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create(['initiative_id' => $initiative->id]);
        VotePosition::factory()->create(['vote_id' => $vote->id, 'party' => 'PS', 'position' => 'favor']);

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.party_overview'));
        $response->assertSee('PS');
    }

    public function test_dashboard_party_grid_links_to_party_pages(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertSee(route('parties.show', 'ps'));
        $response->assertSee(route('parties.show', 'psd'));
    }

    public function test_dashboard_displays_latest_votes_table(): void
    {
        $initiative = Initiative::factory()->approved()->create([
            'title' => 'Test Initiative Title',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertSee(__('ui.dashboard.latest_votes'));
        $response->assertSee('Test Initiative Title');
    }

    public function test_dashboard_renders_with_empty_database(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(__('ui.dashboard.no_votes'));
    }
}
