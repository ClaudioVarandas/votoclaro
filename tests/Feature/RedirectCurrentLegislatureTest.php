<?php

namespace Tests\Feature;

use App\Models\Legislature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectCurrentLegislatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Legislature::factory()->withParties(['PS', 'PSD'])->create([
            'sigla' => 'XVI',
            'number' => 16,
            'start_date' => '2024-03-26',
            'end_date' => '2025-06-02',
        ]);
        Legislature::factory()->xvii()->withParties()->create();
    }

    public function test_the_dashboard_of_the_current_legislature_redirects_to_the_unprefixed_url(): void
    {
        $this->get('/legislatura/XVII')->assertRedirect(route('dashboard'))->assertStatus(301);
    }

    public function test_the_initiatives_of_the_current_legislature_redirect_and_keep_the_query(): void
    {
        $this->get('/legislatura/XVII/iniciativas?status=lapsed&page=2')
            ->assertStatus(301)
            ->assertRedirect(route('initiatives.index', ['status' => 'lapsed', 'page' => 2]));
    }

    public function test_the_parties_of_the_current_legislature_redirect(): void
    {
        $this->get('/legislatura/XVII/partidos')->assertStatus(301)->assertRedirect(route('parties.index'));
    }

    public function test_a_party_of_the_current_legislature_redirects_and_keeps_the_party(): void
    {
        $this->get('/legislatura/XVII/partidos/ps?authored_status=approved')
            ->assertStatus(301)
            ->assertRedirect(route('parties.show', ['party' => 'ps', 'authored_status' => 'approved']));
    }

    public function test_a_lowercase_sigla_of_the_current_legislature_redirects(): void
    {
        $this->get('/legislatura/xvii/iniciativas')->assertStatus(301)->assertRedirect(route('initiatives.index'));
    }

    public function test_an_earlier_legislature_is_not_redirected(): void
    {
        $this->get('/legislatura/XVI/iniciativas')->assertOk();
        $this->get('/legislatura/XVI/partidos/ps')->assertOk();
    }

    public function test_an_unknown_legislature_is_still_not_found(): void
    {
        $this->get('/legislatura/XX/iniciativas')->assertNotFound();
    }
}
