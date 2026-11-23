<?php

namespace Database\Factories;

use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    /**
     * The names the Parliament uses, which its vote records refer to the groups by.
     */
    public const NAMES = [
        'BE' => 'Bloco De Esquerda',
        'CDS-PP' => 'Centro Democrático Social - Partido Popular',
        'CH' => 'Chega',
        'IL' => 'Iniciativa Liberal',
        'JPP' => 'Juntos Pelo Povo',
        'L' => 'Livre',
        'PAN' => 'Pessoas-Animais-Natureza',
        'PCP' => 'Partido Comunista Português',
        'PEV' => 'Partido Ecologista "Os Verdes"',
        'PS' => 'Partido Socialista',
        'PSD' => 'Partido Social Democrata',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'acronym' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->company(),
        ];
    }
}
