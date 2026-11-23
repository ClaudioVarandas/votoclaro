<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\Legislature;
use PHPUnit\Framework\Attributes\Group;
use Tests\Concerns\RefreshesMysqlTestDatabase;
use Tests\TestCase;

#[Group('mysql')]
class BudgetProposalSearchTest extends TestCase
{
    use RefreshesMysqlTestDatabase;

    private Budget $budget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->budget = Budget::factory()->create(['legislature_id' => Legislature::factory()->xvii()->create()->id]);
    }

    public function test_finds_a_whole_word_and_not_the_word_inside_another(): void
    {
        $this->proposal('Redução da taxa do IVA na eletricidade');
        $this->proposal('Medida ativa de emprego');
        $this->proposal('Gestão privativa dos portos');

        $this->assertSame(['Redução da taxa do IVA na eletricidade'], $this->search('IVA'));
    }

    public function test_ignores_case_and_accents(): void
    {
        $this->proposal('Reforço da Saúde Mental');

        $this->assertSame(['Reforço da Saúde Mental'], $this->search('saude mental'));
        $this->assertSame(['Reforço da Saúde Mental'], $this->search('SAÚDE'));
    }

    public function test_finds_words_that_start_with_what_was_typed(): void
    {
        $this->proposal('Programa de habitação acessível');

        $this->assertSame(['Programa de habitação acessível'], $this->search('habita'));
    }

    public function test_requires_every_word(): void
    {
        $this->proposal('Saúde mental nas escolas');
        $this->proposal('Saúde oral');

        $this->assertSame(['Saúde mental nas escolas'], $this->search('saúde mental'));
    }

    public function test_searches_the_text_of_the_articles_too(): void
    {
        $this->proposal('Reforço do Programa de Saúde Sexual e Reprodutiva', 'Consultas especializadas na menopausa e andropausa.');

        $this->assertSame(['Reforço do Programa de Saúde Sexual e Reprodutiva'], $this->search('menopausa'));
    }

    public function test_ignores_the_operators_of_the_search_syntax(): void
    {
        $this->proposal('Redução do IVA');

        $this->assertSame(['Redução do IVA'], $this->search('-IVA* (+"redução")'));
        $this->assertCount(1, BudgetProposal::query()->search('  +-*  ')->get());
    }

    private function proposal(string $title, ?string $text = null): void
    {
        BudgetProposal::factory()->create(['budget_id' => $this->budget->id, 'title' => $title, 'text' => $text]);
    }

    /**
     * @return list<string>
     */
    private function search(string $terms): array
    {
        return BudgetProposal::query()->search($terms)->orderBy('title')->pluck('title')->all();
    }
}
