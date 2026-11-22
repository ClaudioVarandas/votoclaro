<?php

namespace Database\Factories;

use App\Models\Legislature;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Legislature>
 */
class LegislatureFactory extends Factory
{
    /** @var list<string> */
    public const XVII_PARTIES = ['BE', 'CDS-PP', 'CH', 'IL', 'JPP', 'L', 'PAN', 'PCP', 'PS', 'PSD'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 16);

        return [
            'sigla' => $this->toRoman($number),
            'number' => $number,
            'start_date' => fake()->dateTimeBetween('-30 years', '-10 years'),
            'end_date' => fake()->dateTimeBetween('-9 years', '-2 years'),
            'initiatives_url' => fake()->url(),
            'base_info_url' => fake()->url(),
        ];
    }

    public function xvii(): static
    {
        return $this->state(fn () => [
            'sigla' => 'XVII',
            'number' => 17,
            'start_date' => '2025-06-03',
            'end_date' => null,
        ]);
    }

    /**
     * @param  list<string>  $acronyms
     */
    public function withParties(array $acronyms = self::XVII_PARTIES): static
    {
        return $this->afterCreating(function (Legislature $legislature) use ($acronyms): void {
            $legislature->parties()->attach(
                collect($acronyms)->map(fn (string $acronym): int => Party::firstOrCreate(
                    ['acronym' => $acronym],
                    ['name' => $acronym],
                )->id)
            );
        });
    }

    private function toRoman(int $number): string
    {
        $numerals = ['X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $roman = '';

        foreach ($numerals as $numeral => $value) {
            while ($number >= $value) {
                $roman .= $numeral;
                $number -= $value;
            }
        }

        return $roman;
    }
}
