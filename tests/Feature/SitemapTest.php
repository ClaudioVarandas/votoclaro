<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\Legislature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_successful_response(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    }

    public function test_sitemap_contains_expected_urls(): void
    {
        Legislature::factory()->xvii()->withParties()->create();

        $response = $this->get(route('sitemap'));

        $response->assertSee(route('dashboard'));
        $response->assertSee(route('initiatives.index'));
        $response->assertSee(route('parties.index'));
        $response->assertSee(route('about'));
        $response->assertSee(route('parties.show', 'ps'));
        $response->assertSee(route('parties.show', 'psd'));
    }

    public function test_sitemap_lists_the_budgets_and_their_proposals_of_the_current_legislature(): void
    {
        $previous = Legislature::factory()->create(['sigla' => 'XVI', 'number' => 16]);
        $current = Legislature::factory()->xvii()->withParties()->create();
        $budget = Budget::factory()->create(['legislature_id' => $current->id, 'year' => 2027]);
        $old = Budget::factory()->create(['legislature_id' => $previous->id, 'year' => 2025]);
        $proposal = BudgetProposal::factory()->create(['budget_id' => $budget->id, 'slug' => '1907c-menopausa']);
        $oldProposal = BudgetProposal::factory()->create(['budget_id' => $old->id, 'slug' => '1c-antiga']);

        $this->get(route('sitemap'))
            ->assertSee(route('budgets.index'))
            ->assertSee(route('budgets.show', 2027))
            ->assertSee(route('budgets.proposals.index', 2027))
            ->assertSee(route('budgets.proposals.show', ['year' => 2027, 'proposal' => $proposal->slug]))
            ->assertDontSee($oldProposal->slug);
    }
}
