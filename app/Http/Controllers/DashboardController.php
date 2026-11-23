<?php

namespace App\Http\Controllers;

use App\Services\BudgetStatsService;
use App\Services\DashboardService;
use App\Services\LegislatureContext;
use App\Services\PartyStatsService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        LegislatureContext $legislatureContext,
        DashboardService $dashboardService,
        PartyStatsService $partyStatsService,
        BudgetStatsService $budgetStats,
    ): View {
        $legislature = $legislatureContext->activeOrFail();

        return view('pages.dashboard', [
            ...$dashboardService->getInitiativeOverview($legislature),
            ...$dashboardService->getUnanimousStats($legislature),
            'authorship' => $dashboardService->getAuthorshipComparison($legislature),
            'latestInitiatives' => $dashboardService->getLatestInitiatives($legislature),
            'partyTable' => $dashboardService->getPartyTable($legislature, $partyStatsService->getAllPartyStats($legislature)),
            'budgetHighlight' => $budgetStats->highlight($legislature),
        ]);
    }
}
