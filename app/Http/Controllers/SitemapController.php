<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTopic;
use App\Services\LegislatureContext;
use App\Services\PartyStatsService;
use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(LegislatureContext $legislatureContext, PartyStatsService $partyStats): Response
    {
        $legislature = $legislatureContext->current();

        $staticRoutes = [
            route('dashboard'),
            route('initiatives.index'),
            route('budgets.index'),
            route('parties.index'),
            route('about'),
        ];

        $partyRoutes = collect($legislature ? $partyStats->partyAcronyms($legislature) : [])
            ->map(fn (string $acronym): string => route('parties.show', strtolower($acronym)));

        $budgets = Budget::query()->where('legislature_id', $legislature?->id)->orderByDesc('year')->get();

        $budgetRoutes = $budgets->flatMap(fn (Budget $budget): array => [
            route('budgets.show', $budget->year),
            route('budgets.proposals.index', $budget->year),
        ]);

        $proposalRoutes = BudgetProposal::query()
            ->whereIn('budget_id', $budgets->pluck('id'))
            ->with('budget:id,year')
            ->orderBy('id')
            ->get(['id', 'budget_id', 'slug'])
            ->map(fn (BudgetProposal $proposal): string => route('budgets.proposals.show', ['year' => $proposal->budget->year, 'proposal' => $proposal->slug]));

        $topicRoutes = BudgetTopic::query()
            ->whereIn('budget_id', $budgets->pluck('id'))
            ->where('parties_count', '>', 1)
            ->with('budget:id,year')
            ->orderBy('id')
            ->get(['id', 'budget_id', 'slug'])
            ->map(fn (BudgetTopic $topic): string => route('budgets.topics.show', ['year' => $topic->budget->year, 'topic' => $topic->slug]));

        $urls = collect($staticRoutes)->merge($partyRoutes)->merge($budgetRoutes)->merge($topicRoutes)->merge($proposalRoutes);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.htmlspecialchars($url).'</loc></url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'text/xml']);
    }
}
