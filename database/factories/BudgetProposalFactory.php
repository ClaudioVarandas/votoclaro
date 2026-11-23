<?php

namespace Database\Factories;

use App\Enums\BudgetProposalStatus;
use App\Models\Budget;
use App\Models\BudgetProposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetProposal>
 */
class BudgetProposalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 9999).'C';
        $subject = fake()->sentence(4);

        return [
            'id' => fake()->unique()->numberBetween(10000, 99999999),
            'budget_id' => Budget::factory(),
            'number' => $number,
            'slug' => str("{$number} {$subject}")->slug()->toString(),
            'title' => "Artigo 100.º-A ({$subject})",
            'type' => 'Aditamento (Artigo PPL)',
            'scope' => 'Articulado',
            'status' => BudgetProposalStatus::Rejected,
            'status_label' => 'Rejeitado(a) em Comissão',
            'proposer_label' => 'PS',
            'submitted_at' => now(),
        ];
    }
}
