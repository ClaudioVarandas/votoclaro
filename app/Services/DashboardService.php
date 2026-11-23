<?php

namespace App\Services;

use App\Enums\InitiativeStatus;
use App\Models\Initiative;
use App\Models\Legislature;
use App\Models\Vote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const CACHE_TTL = 3600;

    public const GOVERNMENT = 'government';

    public const PARLIAMENTARY_GROUP = 'parliamentary_group';

    /**
     * @return array{totalInitiatives: int, approved: int, rejected: int, inProgress: int, lapsed: int, withdrawn: int, approvalRate: float}
     */
    public function getInitiativeOverview(Legislature $legislature): array
    {
        return Cache::remember("dashboard.{$legislature->id}.initiative_overview", self::CACHE_TTL, function () use ($legislature) {
            $statusCounts = Initiative::query()
                ->forLegislature($legislature)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $total = $statusCounts->sum();
            $approved = $statusCounts->get(InitiativeStatus::Approved->value, 0);

            return [
                'totalInitiatives' => $total,
                'approved' => $approved,
                'rejected' => $statusCounts->get(InitiativeStatus::Rejected->value, 0),
                'inProgress' => $statusCounts->get(InitiativeStatus::InProgress->value, 0),
                'lapsed' => $statusCounts->get(InitiativeStatus::Lapsed->value, 0),
                'withdrawn' => $statusCounts->get(InitiativeStatus::Withdrawn->value, 0),
                'approvalRate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Who proposes the initiatives, the Government or the parliamentary groups, and how far each
     * gets: how many, how many were approved, and the days an approved one took on average.
     *
     * @return array<string, array{total: int, approved: int, approval_rate: float, avg_days: ?int}> by author category
     */
    public function getAuthorshipComparison(Legislature $legislature): array
    {
        return Cache::remember("dashboard.{$legislature->id}.authorship", self::CACHE_TTL, function () use ($legislature): array {
            $rows = Initiative::query()
                ->forLegislature($legislature)
                ->whereIn('author_category', [self::GOVERNMENT, self::PARLIAMENTARY_GROUP])
                ->selectRaw('author_category, COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved', [InitiativeStatus::Approved->value])
                ->selectRaw('AVG(days_to_approval) as avg_days')
                ->groupBy('author_category')
                ->get()
                ->keyBy('author_category');

            return collect([self::GOVERNMENT, self::PARLIAMENTARY_GROUP])
                ->mapWithKeys(function (string $category) use ($rows): array {
                    $total = (int) ($rows[$category]->total ?? 0);
                    $approved = (int) ($rows[$category]->approved ?? 0);
                    $avgDays = $rows[$category]->avg_days ?? null;

                    return [$category => [
                        'total' => $total,
                        'approved' => $approved,
                        'approval_rate' => $total > 0 ? round($approved / $total * 100, 1) : 0.0,
                        'avg_days' => $avgDays !== null ? (int) round((float) $avgDays) : null,
                    ]];
                })
                ->all();
        });
    }

    /**
     * @return array{unanimousCount: int, unanimousPct: float}
     */
    public function getUnanimousStats(Legislature $legislature): array
    {
        return Cache::remember("dashboard.{$legislature->id}.unanimous_stats", self::CACHE_TTL, function () use ($legislature) {
            $latestVotes = Vote::query()
                ->where('is_latest', true)
                ->whereRelation('initiative', 'legislature_id', $legislature->id);

            $totalLatestVotes = (clone $latestVotes)->count();
            $unanimousCount = (clone $latestVotes)->where('unanimous', true)->count();

            return [
                'unanimousCount' => $unanimousCount,
                'unanimousPct' => $totalLatestVotes > 0
                    ? round(($unanimousCount / $totalLatestVotes) * 100, 1)
                    : 0,
            ];
        });
    }

    /**
     * The initiatives each parliamentary group proposed and how many were approved.
     *
     * @return array<string, array{total: int, approved: int, approval_rate: float}> by party acronym
     */
    public function getPartyInitiativeStats(Legislature $legislature): array
    {
        return Cache::remember("dashboard.{$legislature->id}.party_initiatives", self::CACHE_TTL, fn (): array => Initiative::query()
            ->forLegislature($legislature)
            ->where('author_category', self::PARLIAMENTARY_GROUP)
            ->whereNotNull('author_party')
            ->selectRaw('author_party, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved', [InitiativeStatus::Approved->value])
            ->groupBy('author_party')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->author_party => [
                'total' => (int) $row->total,
                'approved' => (int) $row->approved,
                'approval_rate' => round($row->approved / $row->total * 100, 1),
            ]])
            ->all());
    }

    /**
     * The parties of the legislature for the home page: how many initiatives each proposed, how
     * many passed, and how it voted, the most active first.
     *
     * @param  array<string, array{total_votes: int, favor: int, contra: int, abstencao: int, favor_pct: float}>  $voteStats  by acronym
     * @return list<array{acronym: string, initiatives: int, approved: int, approval_rate: float, votes: array{favor: int, contra: int, abstencao: int}, total_votes: int, favor_pct: float}>
     */
    public function getPartyTable(Legislature $legislature, array $voteStats): array
    {
        $initiatives = $this->getPartyInitiativeStats($legislature);

        return collect($voteStats)
            ->map(fn (array $votes, string $acronym): array => [
                'acronym' => $acronym,
                'initiatives' => $initiatives[$acronym]['total'] ?? 0,
                'approved' => $initiatives[$acronym]['approved'] ?? 0,
                'approval_rate' => $initiatives[$acronym]['approval_rate'] ?? 0.0,
                'votes' => ['favor' => $votes['favor'], 'contra' => $votes['contra'], 'abstencao' => $votes['abstencao']],
                'total_votes' => $votes['total_votes'],
                'favor_pct' => (float) $votes['favor_pct'],
            ])
            ->sortBy([['initiatives', 'desc'], ['acronym', 'asc']])
            ->values()
            ->all();
    }

    public function getLatestInitiatives(Legislature $legislature, int $limit = 10): Collection
    {
        return Cache::remember("dashboard.{$legislature->id}.latest_initiatives.{$limit}", self::CACHE_TTL, function () use ($legislature, $limit) {
            return Initiative::query()
                ->forLegislature($legislature)
                ->whereNotNull('final_vote_date')
                ->with('votes')
                ->orderByDesc('final_vote_date')
                ->limit($limit)
                ->get();
        });
    }
}
