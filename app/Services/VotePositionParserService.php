<?php

namespace App\Services;

use Illuminate\Support\Collection;

class VotePositionParserService
{
    /**
     * Parse a single party string into position entries.
     *
     * Format: "<favor_party> [Contra:<contra_party>] [Abstenção:<abstention_party>]"
     *
     * @return array<int, array{party: string, position: string}>
     */
    public function parse(string $partyString): array
    {
        $partyString = trim($partyString);

        if ($partyString === '') {
            return [];
        }

        $positions = [];

        // Extract "Contra:<party>" segments
        if (preg_match_all('/\bContra:(\S+)/', $partyString, $contraMatches)) {
            foreach ($contraMatches[1] as $party) {
                $positions[] = ['party' => $party, 'position' => 'contra'];
            }
            $partyString = preg_replace('/\s*\bContra:\S+/', '', $partyString);
        }

        // Extract "Abstenção:<party>" segments
        if (preg_match_all('/\bAbstenção:(\S+)/u', $partyString, $abstentionMatches)) {
            foreach ($abstentionMatches[1] as $party) {
                $positions[] = ['party' => $party, 'position' => 'abstencao'];
            }
            $partyString = preg_replace('/\s*\bAbstenção:\S+/u', '', $partyString);
        }

        // Remainder is the favor entity
        $favor = trim($partyString);
        if ($favor !== '') {
            array_unshift($positions, ['party' => $favor, 'position' => 'favor']);
        }

        return $positions;
    }

    /**
     * Parse all vote positions from a collection into grouped, deduplicated arrays.
     *
     * @return array{favor: array<string>, contra: array<string>, abstencao: array<string>}
     */
    public function parseAll(Collection $votePositions): array
    {
        $grouped = [
            'favor' => [],
            'contra' => [],
            'abstencao' => [],
        ];

        foreach ($votePositions as $votePosition) {
            $position = $votePosition->position;

            if (isset($grouped[$position])) {
                $grouped[$position][] = $votePosition->party;
            }
        }

        foreach ($grouped as $position => $parties) {
            $grouped[$position] = collect($parties)->unique()->sort()->values()->all();
        }

        return $grouped;
    }
}
