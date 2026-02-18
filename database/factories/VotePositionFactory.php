<?php

namespace Database\Factories;

use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VotePosition>
 */
class VotePositionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vote_id' => Vote::factory(),
            'party' => fake()->randomElement(['PSD', 'PS', 'CH', 'IL', 'BE', 'PCP', 'L', 'PAN', 'CDS-PP', 'JPP']),
            'position' => 'favor',
        ];
    }
}
