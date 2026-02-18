<?php

namespace Tests\Feature;

use App\Models\Initiative;
use App\Models\Vote;
use App\Models\VotePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitiativeDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_successful_response(): void
    {
        $initiative = Initiative::factory()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertStatus(200);
        $response->assertViewIs('pages.initiatives.show');
    }

    public function test_show_displays_initiative_title(): void
    {
        $initiative = Initiative::factory()->create(['title' => 'Proposta de Lei Importante']);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('Proposta de Lei Importante');
    }

    public function test_show_displays_status_badge(): void
    {
        $initiative = Initiative::factory()->approved()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee(__('ui.status.approved'));
    }

    public function test_show_displays_government_badge(): void
    {
        $initiative = Initiative::factory()->government()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee(__('ui.government'));
    }

    public function test_show_displays_votes_with_positions(): void
    {
        $initiative = Initiative::factory()->create();
        $vote = Vote::factory()->create([
            'initiative_id' => $initiative->id,
            'result' => 'Aprovado',
            'date' => '2025-01-15',
        ]);
        VotePosition::factory()->create([
            'vote_id' => $vote->id,
            'party' => 'PSD',
        ]);
        VotePosition::factory()->create([
            'vote_id' => $vote->id,
            'party' => 'JPP Contra:PCP Abstenção:BE',
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('PSD');
        $response->assertSee('JPP');
        $response->assertSee('PCP');
        $response->assertSee('BE');
        $response->assertSee(__('ui.vote_result.Aprovado'));
    }

    public function test_show_displays_empty_votes_message(): void
    {
        $initiative = Initiative::factory()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee(__('ui.initiatives.no_votes'));
    }

    public function test_show_returns_404_for_nonexistent_initiative(): void
    {
        $response = $this->get(route('initiatives.show', 'nonexistent-id'));

        $response->assertStatus(404);
    }

    public function test_show_displays_breadcrumbs(): void
    {
        $initiative = Initiative::factory()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('aria-label="Breadcrumbs"', false);
        $response->assertSee(__('ui.breadcrumbs.dashboard'));
        $response->assertSee(__('ui.breadcrumbs.initiatives'));
        $response->assertSee($initiative->id);
    }

    public function test_show_displays_initiative_type(): void
    {
        $initiative = Initiative::factory()->create([
            'initiative_type_label' => 'Projeto de Lei',
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('Projeto de Lei');
    }

    public function test_show_displays_author_label(): void
    {
        $initiative = Initiative::factory()->parliamentaryGroup('PSD')->create([
            'author_label' => 'Grupo Parlamentar PSD',
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('Grupo Parlamentar PSD');
    }

    public function test_show_links_author_to_party_page(): void
    {
        $initiative = Initiative::factory()->parliamentaryGroup('PS')->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee(route('parties.show', 'ps'), false);
    }

    public function test_show_does_not_link_government_author(): void
    {
        $initiative = Initiative::factory()->government()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertDontSee('parties/');
    }

    public function test_show_displays_approved_duration(): void
    {
        $initiative = Initiative::factory()->approved()->create([
            'days_to_approval' => 49,
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('Aprovado em 49 dias');
    }

    public function test_show_displays_rejected_duration(): void
    {
        $initiative = Initiative::factory()->rejected()->create([
            'entry_date' => '2025-01-01',
            'final_vote_date' => '2025-02-19',
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('Rejeitado em 49 dias');
    }

    public function test_show_displays_in_progress_duration(): void
    {
        $initiative = Initiative::factory()->inProgress()->create([
            'entry_date' => now()->subDays(30),
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('dias em discuss');
    }

    public function test_show_displays_pending_when_dates_null(): void
    {
        $initiative = Initiative::factory()->create([
            'entry_date' => null,
            'final_vote_date' => null,
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee(__('ui.initiatives.pending'));
    }

    public function test_show_displays_type_tooltip_button(): void
    {
        $initiative = Initiative::factory()->create([
            'initiative_type_label' => 'Projeto de Lei',
        ]);

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee('aria-expanded', false);
    }

    public function test_show_government_badge_on_government_author(): void
    {
        $initiative = Initiative::factory()->government()->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertSee(__('ui.government'));
    }

    public function test_show_no_government_badge_on_parliamentary_author(): void
    {
        $initiative = Initiative::factory()->parliamentaryGroup('PS')->create();

        $response = $this->get(route('initiatives.show', $initiative));

        $response->assertDontSee('bg-azure-100', false);
    }
}
