<?php

namespace Tests\Unit;

use App\Services\ParliamentJson;
use PHPUnit\Framework\TestCase;

class ParliamentJsonTest extends TestCase
{
    public function test_a_single_element_becomes_a_list_of_one(): void
    {
        $this->assertSame([['Numero' => '1C']], ParliamentJson::list(['Numero' => '1C']));
    }

    public function test_a_list_stays_a_list(): void
    {
        $this->assertSame([['Numero' => '1C'], ['Numero' => '2C']], ParliamentJson::list([['Numero' => '1C'], ['Numero' => '2C']]));
        $this->assertSame(['Favor', 'Contra'], ParliamentJson::list(['Favor', 'Contra']));
    }

    public function test_nothing_becomes_an_empty_list(): void
    {
        $this->assertSame([], ParliamentJson::list(null));
        $this->assertSame([], ParliamentJson::list(''));
        $this->assertSame(['Artigo 1.º'], ParliamentJson::list('Artigo 1.º'));
    }

    public function test_joins_a_text_given_as_one_or_many(): void
    {
        $this->assertSame('Artigo 2.º', ParliamentJson::text('Artigo 2.º'));
        $this->assertSame('N.º 1, Artigo 2.º; N.º 2, Artigo 2.º', ParliamentJson::text(['N.º 1, Artigo 2.º', ' N.º 2, Artigo 2.º ']));
        $this->assertNull(ParliamentJson::text(null));
        $this->assertNull(ParliamentJson::text(['', '  ']));
    }
}
