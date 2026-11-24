<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\BudgetComparisonService;
use App\Services\BudgetResolver;
use App\Services\BudgetStatsService;
use App\Services\LegislatureContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(private BudgetStatsService $budgetStats) {}

    public function index(LegislatureContext $legislatureContext): View
    {
        $budgets = Budget::query()
            ->where('legislature_id', $legislatureContext->activeOrFail()->id)
            ->orderByDesc('year')
            ->get();

        return view('pages.budgets.index', [
            'budgets' => $budgets->map(fn (Budget $budget): array => [
                'budget' => $budget,
                'overview' => $this->budgetStats->overview($budget),
            ]),
        ]);
    }

    public function show(Request $request, BudgetResolver $resolver, BudgetComparisonService $comparison): View
    {
        $budget = $resolver->budget($request->route('year'), 'budgets.show');
        $budget->load('initiative');

        return view('pages.budgets.show', [
            'budget' => $budget,
            'overview' => $this->budgetStats->overview($budget),
            'government' => $this->budgetStats->governmentOf($budget),
            'parties' => $this->budgetStats->parties($budget),
            'agreement' => $this->budgetStats->agreement($budget),
            'comparison' => $comparison->compare($budget),
            'themes' => $this->budgetStats->themes($budget),
            'topics' => $this->budgetStats->topics($budget),
            'articles' => $this->budgetStats->articles($budget),
        ]);
    }
}
