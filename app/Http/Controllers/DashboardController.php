<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\LegislatureContext;
use App\Services\PartyStatsService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(LegislatureContext $legislatureContext, DashboardService $dashboardService, PartyStatsService $partyStatsService): View
    {
        $legislature = $legislatureContext->activeOrFail();

        return view('pages.dashboard', [
            ...$dashboardService->getInitiativeOverview($legislature),
            ...$dashboardService->getUnanimousStats($legislature),
            'governmentStats' => $dashboardService->getGovernmentStats($legislature),
            'latestInitiatives' => $dashboardService->getLatestInitiatives($legislature),
            'mostActiveParties' => $dashboardService->getMostActiveParties($legislature),
            'leastActiveParties' => $dashboardService->getLeastActiveParties($legislature),
            'highestApprovalParties' => $dashboardService->getHighestApprovalParties($legislature),
            'legislationBalance' => $dashboardService->getLegislationBalance($legislature),
            'partyStats' => $partyStatsService->getAllPartyStats($legislature),
        ]);
    }
}
