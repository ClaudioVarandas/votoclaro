<?php

namespace App\Http\Controllers;

use App\Enums\BudgetProposalStatus;
use App\Http\Requests\IndexBudgetProposalsRequest;
use App\Models\BudgetProposal;
use App\Services\BudgetMatrixService;
use App\Services\BudgetResolver;
use App\Services\BudgetStatsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BudgetProposalController extends Controller
{
    private const PER_PAGE = 50;

    public function __construct(private BudgetResolver $resolver, private BudgetStatsService $budgetStats) {}

    public function index(IndexBudgetProposalsRequest $request, BudgetMatrixService $matrix): View
    {
        $budget = $this->resolver->budget($request->route('year'), 'budgets.proposals.index');
        $themes = $this->budgetStats->themes($budget);
        $theme = $themes->firstWhere('slug', $request->validated('tema'));
        $party = strtoupper((string) $request->validated('partido')) ?: null;
        $status = BudgetProposalStatus::tryFrom((string) $request->validated('estado'));

        $filtered = BudgetProposal::query()
            ->where('budget_id', $budget->id)
            ->when($theme, fn ($query) => $query->whereRelation('themes', 'budget_themes.id', $theme->id))
            ->when($party, fn ($query) => $query->whereRelation('parties', 'acronym', $party))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->validated('q'), fn ($query, string $terms) => $query->search($terms));

        $proposals = (clone $filtered)
            ->with('parties')
            ->orderByRaw('cast(number as unsigned)')
            ->orderBy('number')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $cells = $matrix->cells($filtered);
        $parties = $matrix->votingParties($cells, $this->budgetStats->partyOrder($budget)->all());

        $presentStatuses = BudgetProposal::query()->where('budget_id', $budget->id)->distinct()->toBase()->pluck('status')->all();

        return view('pages.budgets.proposals.index', [
            'budget' => $budget,
            'proposals' => $proposals,
            'cells' => $cells,
            'totals' => $matrix->totals($cells, $parties),
            'parties' => $parties,
            'themes' => $themes,
            'theme' => $theme,
            'proposingParties' => collect($this->budgetStats->parties($budget))->where('proposed', '>', 0)->pluck('acronym')->all(),
            'statuses' => BudgetProposalStatus::listable($presentStatuses),
            'filters' => [
                'tema' => $theme?->slug,
                'partido' => $party,
                'estado' => $status?->value,
                'q' => $request->validated('q'),
            ],
        ]);
    }

    public function show(Request $request): View
    {
        $budget = $this->resolver->budget($request->route('year'), 'budgets.proposals.show', ['proposal' => $request->route('proposal')]);
        $proposal = $this->resolver->proposal($budget, $request->route('proposal'), 'budgets.proposals.show');
        $proposal->load(['parties', 'themes', 'votes.positions']);

        return view('pages.budgets.proposals.show', [
            'budget' => $budget,
            'proposal' => $proposal,
            'parties' => $this->budgetStats->partyOrder($budget)->all(),
        ]);
    }
}
