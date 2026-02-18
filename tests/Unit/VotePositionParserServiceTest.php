<?php

namespace Tests\Unit;

use App\Models\VotePosition;
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

    public function test_parse_all_groups_and_deduplicates(): void
    {
        $positions = collect([
            (object) ['party' => 'PSD'],
            (object) ['party' => 'PS'],
            (object) ['party' => 'JPP Contra:PCP Abstenção:PSD'],
            (object) ['party' => 'BE Abstenção:IL'],
        ]);

        $result = $this->parser->parseAll($positions);

        $this->assertArrayHasKey('favor', $result);
        $this->assertArrayHasKey('contra', $result);
        $this->assertArrayHasKey('abstencao', $result);

        // PSD appears in favor (direct) and abstention (from JPP string) — both should be present
        $this->assertContains('PSD', $result['favor']);
        $this->assertContains('PS', $result['favor']);
        $this->assertContains('JPP', $result['favor']);
        $this->assertContains('BE', $result['favor']);

        $this->assertContains('PCP', $result['contra']);

        $this->assertContains('PSD', $result['abstencao']);
        $this->assertContains('IL', $result['abstencao']);

        // All arrays should be sorted
        $this->assertEquals($result['favor'], collect($result['favor'])->sort()->values()->all());
    }
}
