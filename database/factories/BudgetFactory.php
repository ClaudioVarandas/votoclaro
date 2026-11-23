<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Legislature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legislature_id' => fn (): int => Legislature::query()->orderByDesc('number')->value('id')
                ?? Legislature::factory()->xvii()->withParties()->create()->id,
            'year' => 2027,
            'kind' => Budget::KIND_ORDINARY,
            'proposals_url' => 'https://parlamento.test/OEPropostasAlteracao2027Or_json.txt',
            'budget_url' => null,
            'party_order' => ['PSD', 'CH', 'PS', 'IL', 'L', 'PCP', 'CDS-PP', 'BE', 'PAN'],
        ];
    }
}
