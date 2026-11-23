<?php

namespace Tests\Unit;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVotePosition;
use App\Enums\BudgetVoteStage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BudgetEnumsTest extends TestCase
{
    /**
     * @return array<string, array{string|null, BudgetProposalStatus}>
     */
    public static function statusLabels(): array
    {
        return [
            'approved in committee' => ['Aprovado(a) em Comissão', BudgetProposalStatus::Approved],
            'approved in plenary' => ['Aprovado(a) em Plenário', BudgetProposalStatus::Approved],
            'approved unanimously' => ['Aprovado(a) por Unanimidade em Comissão', BudgetProposalStatus::Approved],
            'approved with changes' => ['Aprovado(a) em Comissão com Alterações', BudgetProposalStatus::Approved],
            'partially approved' => ['Aprovado(a) Parcialmente em Comissão', BudgetProposalStatus::PartiallyApproved],
            'rejected in committee' => ['Rejeitado(a) em Comissão', BudgetProposalStatus::Rejected],
            'rejected in plenary' => ['Rejeitado(a) em Plenário', BudgetProposalStatus::Rejected],
            'awaiting the vote' => ['Aguarda Voto em Comissão', BudgetProposalStatus::Pending],
            'called up to the plenary' => ['Avocado(a)', BudgetProposalStatus::Pending],
            'sent to the plenary' => ['Remetido(a) a Plenário', BudgetProposalStatus::Pending],
            'withdrawn' => ['Retirado(a)', BudgetProposalStatus::Withdrawn],
            'replaced' => ['Substituído(a)', BudgetProposalStatus::Replaced],
            'not admitted' => ['Não Admitida', BudgetProposalStatus::NotAdmitted],
            'prejudiced' => ['Prejudicado(a)', BudgetProposalStatus::Prejudiced],
            'unknown' => ['Algo novo', BudgetProposalStatus::Pending],
            'missing' => [null, BudgetProposalStatus::Pending],
        ];
    }

    #[DataProvider('statusLabels')]
    public function test_reads_the_status_the_parliament_writes(?string $label, BudgetProposalStatus $expected): void
    {
        $this->assertSame($expected, BudgetProposalStatus::fromLabel($label));
    }

    public function test_a_vote_is_in_the_plenary_only_when_its_result_says_so(): void
    {
        $this->assertSame(BudgetVoteStage::Plenary, BudgetVoteStage::fromResultLabel('Rejeitado(a) em Plenário'));
        $this->assertSame(BudgetVoteStage::Committee, BudgetVoteStage::fromResultLabel('Rejeitado(a) em Comissão'));
        $this->assertSame(BudgetVoteStage::Committee, BudgetVoteStage::fromResultLabel(null));
    }

    public function test_reads_each_position(): void
    {
        $this->assertSame(BudgetVotePosition::Favor, BudgetVotePosition::fromLabel('Favor'));
        $this->assertSame(BudgetVotePosition::Contra, BudgetVotePosition::fromLabel('Contra'));
        $this->assertSame(BudgetVotePosition::Abstencao, BudgetVotePosition::fromLabel('Abstenção'));
        $this->assertSame(BudgetVotePosition::Ausente, BudgetVotePosition::fromLabel('Ausente'));
        $this->assertNull(BudgetVotePosition::fromLabel('Talvez'));
    }
}
