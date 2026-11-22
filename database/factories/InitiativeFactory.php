<?php

namespace Database\Factories;

use App\Enums\InitiativeStatus;
use App\Models\Legislature;
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
            'legislature_id' => fn (): int => Legislature::query()->orderByDesc('number')->value('id')
                ?? Legislature::factory()->xvii()->withParties()->create()->id,
            'title' => fake()->sentence(8),
            'status' => fake()->randomElement(InitiativeStatus::core()),
            'entry_date' => $entryDate,
            'final_vote_date' => fake()->optional(0.7)->dateTimeBetween($entryDate, 'now'),
            'days_to_approval' => fake()->optional(0.5)->numberBetween(1, 365),
            'author_category' => $authorCategory,
            'author_party' => $authorCategory === 'parliamentary_group' ? fake()->randomElement(['PS', 'PSD', 'CH', 'IL', 'BE', 'PCP', 'L', 'PAN']) : null,
            'author_label' => $authorCategory === 'government' ? 'Governo' : 'Grupo Parlamentar '.fake()->randomElement(['PS', 'PSD', 'CH', 'IL']),
            'initiative_type' => fake()->randomElement(['pjl', 'ppl', 'pjr', 'ppr', 'pjd', 'ip', 'ap']),
            'initiative_type_label' => fake()->randomElement(['Projeto de Lei', 'Proposta de Lei', 'Projeto de Resolução', 'Proposta de Resolução']),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => InitiativeStatus::Approved,
            'final_vote_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => InitiativeStatus::Rejected,
            'final_vote_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => InitiativeStatus::InProgress,
            'final_vote_date' => null,
            'days_to_approval' => null,
        ]);
    }

    public function lapsed(): static
    {
        return $this->state(fn () => [
            'status' => InitiativeStatus::Lapsed,
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn () => [
            'status' => InitiativeStatus::Withdrawn,
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
            'author_label' => 'Grupo Parlamentar '.$party,
        ]);
    }
}
