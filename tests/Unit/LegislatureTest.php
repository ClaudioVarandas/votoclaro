<?php

namespace Tests\Unit;

use App\Models\Legislature;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LegislatureTest extends TestCase
{
    /**
     * @return array<string, array{string, int}>
     */
    public static function siglas(): array
    {
        return [
            'I' => ['I', 1],
            'IV' => ['IV', 4],
            'IX' => ['IX', 9],
            'XIV' => ['XIV', 14],
            'XVII' => ['XVII', 17],
            'XVIII' => ['XVIII', 18],
            'XIX' => ['XIX', 19],
            'XL' => ['XL', 40],
            'lowercase with spaces' => [' xviii ', 18],
        ];
    }

    #[DataProvider('siglas')]
    public function test_number_from_sigla(string $sigla, int $expected): void
    {
        $this->assertSame($expected, Legislature::numberFromSigla($sigla));
    }

    public function test_number_from_sigla_rejects_non_roman_numerals(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Legislature::numberFromSigla('17');
    }

    public function test_number_from_sigla_rejects_an_empty_sigla(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Legislature::numberFromSigla('');
    }
}
