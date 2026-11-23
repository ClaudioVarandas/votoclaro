<?php

namespace Database\Factories;

use App\Enums\BudgetProposalStatus;
use App\Models\Budget;
use App\Models\BudgetArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetArticle>
 */
class BudgetArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 999);
        $title = fake()->sentence(4);

        return [
            'id' => fake()->unique()->numberBetween(70000, 99999),
            'budget_id' => Budget::factory(),
            'position' => $number,
            'number' => "Artigo {$number}.º",
            'number_slug' => "artigo-{$number}",
            'slug' => str("artigo-{$number} {$title}")->slug()->toString(),
            'title' => $title,
            'text' => fake()->paragraph(),
            'status' => BudgetProposalStatus::Pending,
            'status_label' => 'Entrada',
            'law_title' => null,
            'law_subtitle' => null,
        ];
    }
}
