<?php

namespace App\Http\Controllers;

use App\Services\PartyStatsService;
use Illuminate\Contracts\View\View;

class PartyController extends Controller
{
    public function index(PartyStatsService $partyStats): View
    {
        return view('pages.parties.index', [
            'partyStats' => $partyStats->getAllPartyStats(),
        ]);
    }

    public function show(string $party, PartyStatsService $partyStats): View
    {
        $acronym = strtoupper($party);
        $stats = $partyStats->getPartyStats($acronym);

        if ($stats === null) {
            abort(404);
        }

        return view('pages.parties.show', [
            'stats' => $stats,
            'trend' => $partyStats->getMonthlyTrend($acronym),
        ]);
    }
}
