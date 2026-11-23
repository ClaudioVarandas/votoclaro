<?php

namespace Tests\Feature;

use App\Enums\InitiativeStatus;
use App\Models\Initiative;
use App\Models\Legislature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LapsedAndWithdrawnInitiativesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Legislature::factory()->xvii()->withParties()->create();
    }

    public function test_initiative_page_shows_the_lapsed_status(): void
    {
        $initiative = Initiative::factory()->lapsed()->create();

        $this->get(route('initiatives.show', $initiative))
            ->assertOk()
            ->assertSee(InitiativeStatus::Lapsed->label());
    }

    public function test_initiative_page_shows_the_withdrawn_status(): void
    {
        $initiative = Initiative::factory()->withdrawn()->create();

        $this->get(route('initiatives.show', $initiative))
            ->assertOk()
            ->assertSee(InitiativeStatus::Withdrawn->label());
    }

    public function test_initiative_list_offers_only_core_filters_when_nothing_lapsed(): void
    {
        Initiative::factory()->approved()->create();

        $this->get(route('initiatives.index'))
            ->assertViewHas('statuses', InitiativeStatus::core())
            ->assertDontSee(InitiativeStatus::Lapsed->label());
    }

    public function test_initiative_list_offers_the_lapsed_filter_when_some_lapsed(): void
    {
        Initiative::factory()->lapsed()->create();

        $this->get(route('initiatives.index'))
            ->assertViewHas('statuses', [...InitiativeStatus::core(), InitiativeStatus::Lapsed]);
    }

    public function test_initiative_list_filters_by_lapsed(): void
    {
        Initiative::factory()->lapsed()->create(['title' => 'Projeto que caducou']);
        Initiative::factory()->approved()->create(['title' => 'Projeto aprovado']);

        $this->get(route('initiatives.index', ['status' => 'lapsed']))
            ->assertSee('Projeto que caducou')
            ->assertDontSee('Projeto aprovado');
    }

    public function test_initiative_list_ignores_an_unknown_status_filter(): void
    {
        Initiative::factory()->approved()->create(['title' => 'Projeto aprovado']);

        $this->get(route('initiatives.index', ['status' => 'nonsense']))
            ->assertOk()
            ->assertSee('Projeto aprovado');
    }

    public function test_party_page_counts_and_offers_lapsed_and_withdrawn(): void
    {
        Initiative::factory()->parliamentaryGroup('PS')->lapsed()->count(2)->create();
        Initiative::factory()->parliamentaryGroup('PS')->withdrawn()->create();
        Initiative::factory()->parliamentaryGroup('PS')->approved()->create();

        $this->get(route('parties.show', 'ps'))
            ->assertOk()
            ->assertViewHas('authoredStatusCounts', fn (array $counts): bool => $counts['total'] === 4
                && $counts['lapsed'] === 2
                && $counts['withdrawn'] === 1)
            ->assertViewHas('authoredStatuses', InitiativeStatus::cases())
            ->assertSee(InitiativeStatus::Lapsed->label().' (2)');
    }

    public function test_party_page_filters_its_initiatives_by_lapsed(): void
    {
        Initiative::factory()->parliamentaryGroup('PS')->lapsed()->create(['title' => 'Projeto PS caducado']);
        Initiative::factory()->parliamentaryGroup('PS')->approved()->create(['title' => 'Projeto PS aprovado']);

        $this->get(route('parties.show', ['ps', 'authored_status' => 'lapsed']))
            ->assertOk()
            ->assertSee('Projeto PS caducado')
            ->assertDontSee('Projeto PS aprovado');
    }

    public function test_party_page_rejects_an_unknown_status(): void
    {
        $this->get(route('parties.show', ['ps', 'authored_status' => 'nonsense']))
            ->assertSessionHasErrors('authored_status');
    }

    public function test_dashboard_shows_lapsed_and_withdrawn_counts_when_present(): void
    {
        Initiative::factory()->lapsed()->count(3)->create();
        Initiative::factory()->withdrawn()->create();
        Initiative::factory()->approved()->create();

        $this->get(route('dashboard'))
            ->assertViewHas('lapsed', 3)
            ->assertViewHas('withdrawn', 1)
            ->assertViewHas('totalInitiatives', 5)
            ->assertSee(__('ui.dashboard.outcome.lapsed'))
            ->assertSee(__('ui.dashboard.outcome.withdrawn'));
    }

    public function test_dashboard_hides_lapsed_and_withdrawn_when_absent(): void
    {
        Initiative::factory()->approved()->create();

        $this->get(route('dashboard'))
            ->assertDontSee(__('ui.dashboard.outcome.lapsed'))
            ->assertDontSee(__('ui.dashboard.outcome.withdrawn'));
    }
}
