<?php

namespace Tests\Unit;

use App\Services\VotePositionParserService;
use PHPUnit\Framework\TestCase;

class VotePositionParserServiceTest extends TestCase
{
    private VotePositionParserService $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new VotePositionParserService;
    }

    public function test_parse_simple_party(): void
    {
        $result = $this->parser->parse('PSD');

        $this->assertCount(1, $result);
        $this->assertEquals(['party' => 'PSD', 'position' => 'favor'], $result[0]);
    }

    public function test_parse_party_with_contra(): void
    {
        $result = $this->parser->parse('JPP Contra:L');

        $this->assertCount(2, $result);
        $this->assertEquals(['party' => 'JPP', 'position' => 'favor'], $result[0]);
        $this->assertEquals(['party' => 'L', 'position' => 'contra'], $result[1]);
    }

    public function test_parse_party_with_abstention(): void
    {
        $result = $this->parser->parse('BE Abstenção:IL');

        $this->assertCount(2, $result);
        $this->assertEquals(['party' => 'BE', 'position' => 'favor'], $result[0]);
        $this->assertEquals(['party' => 'IL', 'position' => 'abstencao'], $result[1]);
    }

    public function test_parse_party_with_contra_and_abstention(): void
    {
        $result = $this->parser->parse('JPP Contra:PCP Abstenção:PSD');

        $this->assertCount(3, $result);
        $this->assertEquals(['party' => 'JPP', 'position' => 'favor'], $result[0]);
        $this->assertEquals(['party' => 'PCP', 'position' => 'contra'], $result[1]);
        $this->assertEquals(['party' => 'PSD', 'position' => 'abstencao'], $result[2]);
    }

    public function test_parse_numbered_party(): void
    {
        $result = $this->parser->parse('CDS-PP Abstenção:1-PS');

        $this->assertCount(2, $result);
        $this->assertEquals(['party' => 'CDS-PP', 'position' => 'favor'], $result[0]);
        $this->assertEquals(['party' => '1-PS', 'position' => 'abstencao'], $result[1]);
    }

    public function test_parse_party_contra_with_hyphen(): void
    {
        $result = $this->parser->parse('PAN Contra:PSD');

        $this->assertCount(2, $result);
        $this->assertEquals(['party' => 'PAN', 'position' => 'favor'], $result[0]);
        $this->assertEquals(['party' => 'PSD', 'position' => 'contra'], $result[1]);
    }

    public function test_parse_empty_string(): void
    {
        $result = $this->parser->parse('');

        $this->assertCount(0, $result);
    }

    public function test_parse_all_groups_by_position_column(): void
    {
        $positions = collect([
            (object) ['party' => 'PSD', 'position' => 'favor'],
            (object) ['party' => 'PS', 'position' => 'favor'],
            (object) ['party' => 'JPP', 'position' => 'favor'],
            (object) ['party' => 'BE', 'position' => 'contra'],
            (object) ['party' => 'PCP', 'position' => 'contra'],
            (object) ['party' => 'IL', 'position' => 'abstencao'],
        ]);

        $result = $this->parser->parseAll($positions);

        $this->assertArrayHasKey('favor', $result);
        $this->assertArrayHasKey('contra', $result);
        $this->assertArrayHasKey('abstencao', $result);

        $this->assertEquals(['JPP', 'PS', 'PSD'], $result['favor']);
        $this->assertEquals(['BE', 'PCP'], $result['contra']);
        $this->assertEquals(['IL'], $result['abstencao']);
    }

    public function test_parse_all_deduplicates_parties(): void
    {
        $positions = collect([
            (object) ['party' => 'PSD', 'position' => 'favor'],
            (object) ['party' => 'PSD', 'position' => 'favor'],
            (object) ['party' => 'BE', 'position' => 'contra'],
        ]);

        $result = $this->parser->parseAll($positions);

        $this->assertCount(1, $result['favor']);
        $this->assertContains('PSD', $result['favor']);
    }

    public function test_parse_all_ignores_unknown_positions(): void
    {
        $positions = collect([
            (object) ['party' => 'PSD', 'position' => 'favor'],
            (object) ['party' => 'BE', 'position' => 'unknown'],
        ]);

        $result = $this->parser->parseAll($positions);

        $this->assertCount(1, $result['favor']);
        $this->assertEmpty($result['contra']);
        $this->assertEmpty($result['abstencao']);
    }
}
