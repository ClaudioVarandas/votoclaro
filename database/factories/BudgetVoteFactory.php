<?php

namespace Database\Factories;

use App\Enums\BudgetProposalStatus;
use App\Enums\BudgetVoteStage;
use App\Models\BudgetProposal;
use App\Models\BudgetVote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetVote>
 */
class BudgetVoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_proposal_id' => BudgetProposal::factory(),
            'sequence' => 1,
            'stage' => BudgetVoteStage::Committee,
            'date' => now()->toDateString(),
            'result' => BudgetProposalStatus::Rejected,
            'result_label' => 'Rejeitado(a) em Comissão',
        ];
    }
}
