<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Initiative>
 */
class InitiativeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entryDate = fake()->dateTimeBetween('-2 years', '-1 month');
        $authorCategory = fake()->randomElement(['parliamentary_group', 'government']);

        return [
            'id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'title' => fake()->sentence(8),
            'status' => fake()->randomElement(['approved', 'rejected', 'in_progress']),
            'entry_date' => $entryDate,
            'final_vote_date' => fake()->optional(0.7)->dateTimeBetween($entryDate, 'now'),
            'days_to_approval' => fake()->optional(0.5)->numberBetween(1, 365),
            'author_category' => $authorCategory,
            'author_party' => $authorCategory === 'parliamentary_group' ? fake()->randomElement(['PS', 'PSD', 'CH', 'IL', 'BE', 'PCP', 'L', 'PAN']) : null,
            'author_label' => $authorCategory === 'government' ? 'Governo' : 'Grupo Parlamentar ' . fake()->randomElement(['PS', 'PSD', 'CH', 'IL']),
            'initiative_type' => fake()->randomElement(['pjl', 'ppl', 'pjr', 'ppr', 'pjd', 'ip', 'ap']),
            'initiative_type_label' => fake()->randomElement(['Projeto de Lei', 'Proposta de Lei', 'Projeto de Resolução', 'Proposta de Resolução']),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'final_vote_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'final_vote_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => 'in_progress',
            'final_vote_date' => null,
            'days_to_approval' => null,
        ]);
    }

    public function government(): static
    {
        return $this->state(fn () => [
            'author_category' => 'government',
            'author_label' => 'Governo',
            'author_party' => null,
        ]);
    }

    public function parliamentaryGroup(string $party = 'PS'): static
    {
        return $this->state(fn () => [
            'author_category' => 'parliamentary_group',
            'author_party' => $party,
            'author_label' => 'Grupo Parlamentar ' . $party,
        ]);
    }
}
