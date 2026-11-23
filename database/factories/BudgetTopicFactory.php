<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetTopic>
 */
class BudgetTopicFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = ucfirst(fake()->unique()->word());

        return [
            'budget_id' => Budget::factory(),
            'slug' => str($label)->slug()->toString(),
            'label' => $label,
            'proposals_count' => 10,
            'parties_count' => 3,
            'voted_count' => 8,
            'division' => 0.5,
        ];
    }
}
