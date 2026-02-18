<?php

namespace Database\Factories;

use App\Models\Initiative;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vote>
 */
class VoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->uuid(),
            'initiative_id' => Initiative::factory(),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'result' => fake()->randomElement(['Aprovado', 'Rejeitado', 'Prejudicado']),
            'unanimous' => fake()->boolean(10),
        ];
    }
}
