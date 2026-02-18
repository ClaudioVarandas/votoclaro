<?php

namespace Tests\Unit;

use App\Enums\VoteResult;
use PHPUnit\Framework\TestCase;

class VoteResultEnumTest extends TestCase
{
    public function test_has_correct_cases(): void
    {
        $this->assertSame('Aprovado', VoteResult::Aprovado->value);
        $this->assertSame('Rejeitado', VoteResult::Rejeitado->value);
        $this->assertSame('Prejudicado', VoteResult::Prejudicado->value);
    }

    public function test_color_returns_correct_classes(): void
    {
        $this->assertStringContainsString('republic', VoteResult::Aprovado->color());
        $this->assertStringContainsString('parliament', VoteResult::Rejeitado->color());
        $this->assertStringContainsString('sand', VoteResult::Prejudicado->color());
    }

    public function test_can_be_created_from_value(): void
    {
        $this->assertSame(VoteResult::Aprovado, VoteResult::from('Aprovado'));
        $this->assertSame(VoteResult::Rejeitado, VoteResult::from('Rejeitado'));
        $this->assertSame(VoteResult::Prejudicado, VoteResult::from('Prejudicado'));
    }
}
