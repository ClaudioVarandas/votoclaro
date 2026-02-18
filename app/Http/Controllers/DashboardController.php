<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\PartyStatsService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboardService, PartyStatsService $partyStatsService): View
    {
        $overview = $dashboardService->getInitiativeOverview();
        $unanimousStats = $dashboardService->getUnanimousStats();

        return view('pages.dashboard', [
            ...$overview,
            ...$unanimousStats,
            'governmentStats' => $dashboardService->getGovernmentStats(),
            'latestInitiatives' => $dashboardService->getLatestInitiatives(),
            'mostActiveParties' => $dashboardService->getMostActiveParties(),
            'leastActiveParties' => $dashboardService->getLeastActiveParties(),
            'highestApprovalParties' => $dashboardService->getHighestApprovalParties(),
            'legislationBalance' => $dashboardService->getLegislationBalance(),
            'partyStats' => $partyStatsService->getAllPartyStats(),
        ]);
    }
}
