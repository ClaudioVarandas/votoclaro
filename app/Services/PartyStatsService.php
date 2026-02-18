<?php

namespace App\Services;

use App\Models\VotePosition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PartyStatsService
{
    /** @var array<string> */
    public const MAIN_PARTIES = [
        'BE', 'CDS-PP', 'CH', 'IL', 'JPP', 'L', 'PAN', 'PCP', 'PS', 'PSD',
    ];

    public function __construct(private VotePositionParserService $parser) {}

    /**
     * Get aggregate stats for all main parties.
     *
     * @return array<string, array{acronym: string, total_votes: int, favor: int, contra: int, abstencao: int, favor_pct: float, contra_pct: float, abstencao_pct: float, government_alignment: float}>
     */
    public function getAllPartyStats(): array
    {
        return Cache::remember('party_stats.all', 3600, function () {
            $parsed = $this->buildParsedPositions();
            $governmentVoteIds = $this->getGovernmentVoteIds();

            $stats = [];
            foreach (self::MAIN_PARTIES as $acronym) {
                $stats[$acronym] = $this->computeStats($acronym, $parsed, $governmentVoteIds);
            }

            return $stats;
        });
    }

    /**
     * Get stats for a single party.
     *
     * @return array{acronym: string, total_votes: int, favor: int, contra: int, abstencao: int, favor_pct: float, contra_pct: float, abstencao_pct: float, government_alignment: float}|null
     */
    public function getPartyStats(string $acronym): ?array
    {
        $acronym = strtoupper($acronym);

        if (! in_array($acronym, self::MAIN_PARTIES, true)) {
            return null;
        }

        return Cache::remember("party_stats.{$acronym}", 3600, function () use ($acronym) {
            $parsed = $this->buildParsedPositions();
            $governmentVoteIds = $this->getGovernmentVoteIds();

            return $this->computeStats($acronym, $parsed, $governmentVoteIds);
        });
    }

    /**
     * Get monthly voting trend for a party.
     *
     * @return array<int, array{month: string, favor: int, contra: int, abstencao: int, total: int}>
     */
    public function getMonthlyTrend(string $acronym): array
    {
        $acronym = strtoupper($acronym);

        return Cache::remember("party_stats.trend.{$acronym}", 3600, function () use ($acronym) {
            $positions = VotePosition::query()
                ->join('votes', 'vote_positions.vote_id', '=', 'votes.id')
                ->whereNotNull('votes.date')
                ->select('vote_positions.party', 'vote_positions.vote_id', 'votes.date')
                ->get();

            $monthly = [];

            foreach ($positions as $position) {
                $entries = $this->parser->parse($position->party);

                foreach ($entries as $entry) {
                    if ($entry['party'] !== $acronym) {
                        continue;
                    }

                    $month = substr($position->date, 0, 7);
                    $key = $position->vote_id . '-' . $entry['position'];

                    if (! isset($monthly[$month])) {
                        $monthly[$month] = ['month' => $month, 'favor' => 0, 'contra' => 0, 'abstencao' => 0, 'total' => 0, '_seen' => []];
                    }

                    $voteKey = $position->vote_id . '-' . $entry['position'];
                    if (! in_array($voteKey, $monthly[$month]['_seen'], true)) {
                        $monthly[$month][$entry['position']]++;
                        $monthly[$month]['total']++;
                        $monthly[$month]['_seen'][] = $voteKey;
                    }
                }
            }

            ksort($monthly);

            return array_values(array_map(function ($item) {
                unset($item['_seen']);

                return $item;
            }, $monthly));
        });
    }

    /**
     * Parse all vote positions and return a flat collection of parsed entries with vote metadata.
     *
     * @return Collection<int, array{vote_id: string, party: string, position: string, is_government: bool}>
     */
    private function buildParsedPositions(): Collection
    {
        $positions = VotePosition::query()
            ->select('vote_id', 'party')
            ->get();

        $parsed = collect();

        foreach ($positions as $position) {
            $entries = $this->parser->parse($position->party);

            foreach ($entries as $entry) {
                if (in_array($entry['party'], self::MAIN_PARTIES, true)) {
                    $parsed->push([
                        'vote_id' => $position->vote_id,
                        'party' => $entry['party'],
                        'position' => $entry['position'],
                    ]);
                }
            }
        }

        return $parsed;
    }

    /**
     * Get all vote IDs that belong to government initiatives.
     *
     * @return array<string>
     */
    private function getGovernmentVoteIds(): array
    {
        return VotePosition::query()
            ->join('votes', 'vote_positions.vote_id', '=', 'votes.id')
            ->join('initiatives', 'votes.initiative_id', '=', 'initiatives.id')
            ->where('initiatives.author_category', 'government')
            ->distinct()
            ->pluck('votes.id')
            ->all();
    }

    /**
     * Compute stats for a single party from pre-parsed data.
     *
     * @return array{acronym: string, total_votes: int, favor: int, contra: int, abstencao: int, favor_pct: float, contra_pct: float, abstencao_pct: float, government_alignment: float}
     */
    private function computeStats(string $acronym, Collection $parsed, array $governmentVoteIds): array
    {
        $partyPositions = $parsed->where('party', $acronym);

        $uniqueVotes = $partyPositions->pluck('vote_id')->unique();
        $totalVotes = $uniqueVotes->count();

        $favor = $partyPositions->where('position', 'favor')->pluck('vote_id')->unique()->count();
        $contra = $partyPositions->where('position', 'contra')->pluck('vote_id')->unique()->count();
        $abstencao = $partyPositions->where('position', 'abstencao')->pluck('vote_id')->unique()->count();

        $governmentVotes = $uniqueVotes->intersect($governmentVoteIds)->count();
        $governmentFavor = $partyPositions
            ->where('position', 'favor')
            ->whereIn('vote_id', $governmentVoteIds)
            ->pluck('vote_id')
            ->unique()
            ->count();

        return [
            'acronym' => $acronym,
            'total_votes' => $totalVotes,
            'favor' => $favor,
            'contra' => $contra,
            'abstencao' => $abstencao,
            'favor_pct' => $totalVotes > 0 ? round(($favor / $totalVotes) * 100, 1) : 0,
            'contra_pct' => $totalVotes > 0 ? round(($contra / $totalVotes) * 100, 1) : 0,
            'abstencao_pct' => $totalVotes > 0 ? round(($abstencao / $totalVotes) * 100, 1) : 0,
            'government_alignment' => $governmentVotes > 0 ? round(($governmentFavor / $governmentVotes) * 100, 1) : 0,
        ];
    }
}
