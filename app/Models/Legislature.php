<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Legislature extends Model
{
    /** @use HasFactory<\Database\Factories\LegislatureFactory> */
    use HasFactory;

    private const ROMAN_VALUES = ['M' => 1000, 'D' => 500, 'C' => 100, 'L' => 50, 'X' => 10, 'V' => 5, 'I' => 1];

    protected $fillable = [
        'sigla',
        'number',
        'start_date',
        'end_date',
        'initiatives_url',
        'base_info_url',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class)->withTimestamps();
    }

    public function isClosed(): bool
    {
        return $this->end_date !== null;
    }

    public static function numberFromSigla(string $sigla): int
    {
        $sigla = strtoupper(trim($sigla));

        if (! preg_match('/^[MDCLXVI]+$/', $sigla)) {
            throw new InvalidArgumentException("[{$sigla}] is not a roman numeral.");
        }

        $number = 0;
        $length = strlen($sigla);

        for ($i = 0; $i < $length; $i++) {
            $value = self::ROMAN_VALUES[$sigla[$i]];
            $next = $i + 1 < $length ? self::ROMAN_VALUES[$sigla[$i + 1]] : 0;
            $number += $value < $next ? -$value : $value;
        }

        return $number;
    }
}
