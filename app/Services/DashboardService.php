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

    public function getGovernmentStats(Legislature $legislature): object
    {
        return Cache::remember("dashboard.{$legislature->id}.government_stats", self::CACHE_TTL, function () use ($legislature) {
            return Initiative::query()
                ->forLegislature($legislature)
                ->where('author_category', 'government')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved', [InitiativeStatus::Approved->value])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected', [InitiativeStatus::Rejected->value])
                ->selectRaw('AVG(days_to_approval) as avg_days')
                ->first();
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

    public function getMostActiveParties(Legislature $legislature, int $limit = 3): Collection
    {
        return Cache::remember("dashboard.{$legislature->id}.most_active_parties.{$limit}", self::CACHE_TTL, function () use ($legislature, $limit) {
            return Initiative::query()
                ->forLegislature($legislature)
                ->where('author_category', 'parliamentary_group')
                ->whereNotNull('author_party')
                ->selectRaw('author_party, COUNT(*) as total')
                ->groupBy('author_party')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        });
    }

    public function getLeastActiveParties(Legislature $legislature, int $limit = 3): Collection
    {
        return Cache::remember("dashboard.{$legislature->id}.least_active_parties.{$limit}", self::CACHE_TTL, function () use ($legislature, $limit) {
            return Initiative::query()
                ->forLegislature($legislature)
                ->where('author_category', 'parliamentary_group')
                ->whereNotNull('author_party')
                ->selectRaw('author_party, COUNT(*) as total')
                ->groupBy('author_party')
                ->orderBy('total')
                ->limit($limit)
                ->get();
        });
    }

    public function getHighestApprovalParties(Legislature $legislature, int $minThreshold = 10, int $limit = 3): Collection
    {
        return Cache::remember("dashboard.{$legislature->id}.highest_approval_parties.{$minThreshold}.{$limit}", self::CACHE_TTL, function () use ($legislature, $minThreshold, $limit) {
            return Initiative::query()
                ->forLegislature($legislature)
                ->where('author_category', 'parliamentary_group')
                ->whereNotNull('author_party')
                ->selectRaw('author_party, COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_count', [InitiativeStatus::Approved->value])
                ->groupBy('author_party')
                ->havingRaw('COUNT(*) >= ?', [$minThreshold])
                ->get()
                ->map(fn ($row) => (object) [
                    'author_party' => $row->author_party,
                    'total' => $row->total,
                    'approved_count' => $row->approved_count,
                    'approval_rate' => round(($row->approved_count / $row->total) * 100, 1),
                ])
                ->sortByDesc('approval_rate')
                ->take($limit)
                ->values();
        });
    }

    public function getLegislationBalance(Legislature $legislature): object
    {
        return Cache::remember("dashboard.{$legislature->id}.legislation_balance", self::CACHE_TTL, function () use ($legislature) {
            return Initiative::query()
                ->forLegislature($legislature)
                ->selectRaw("SUM(CASE WHEN author_category = 'government' THEN 1 ELSE 0 END) as gov")
                ->selectRaw("SUM(CASE WHEN author_category = 'parliamentary_group' THEN 1 ELSE 0 END) as parl")
                ->first();
        });
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
