<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalInitiatives = Initiative::query()->count();

        $statusCounts = Initiative::query()
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        $approved = $statusCounts->get('approved', 0);
        $rejected = $statusCounts->get('rejected', 0);
        $inProgress = $statusCounts->get('in_progress', 0);

        $governmentStats = Initiative::query()
            ->where('author_category', 'government')
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->selectRaw("AVG(days_to_approval) as avg_days")
            ->first();

        $latestInitiatives = Initiative::query()
            ->whereNotNull('final_vote_date')
            ->with('votes')
            ->orderByDesc('final_vote_date')
            ->limit(10)
            ->get();

        return view('pages.dashboard', [
            'totalInitiatives' => $totalInitiatives,
            'approved' => $approved,
            'rejected' => $rejected,
            'inProgress' => $inProgress,
            'approvalRate' => $totalInitiatives > 0
                ? round(($approved / $totalInitiatives) * 100, 1)
                : 0,
            'governmentStats' => $governmentStats,
            'latestInitiatives' => $latestInitiatives,
        ]);
    }
}
