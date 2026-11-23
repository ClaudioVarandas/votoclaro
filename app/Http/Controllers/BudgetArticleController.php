<?php

namespace App\Http\Controllers;

use App\Enums\BudgetProposalStatus;
use App\Http\Requests\IndexBudgetArticlesRequest;
use App\Models\BudgetArticle;
use App\Services\BudgetMatrixService;
use App\Services\BudgetResolver;
use App\Services\BudgetStatsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BudgetArticleController extends Controller
{
    private const PER_PAGE = 50;

    public function __construct(private BudgetResolver $resolver, private BudgetStatsService $budgetStats) {}

    public function index(IndexBudgetArticlesRequest $request, BudgetMatrixService $matrix): View
    {
        $budget = $this->resolver->budget($request->route('year'), 'budgets.articles.index');
        $status = BudgetProposalStatus::tryFrom((string) $request->validated('estado'));
        $amendsLaw = $request->boolean('lei');
        $terms = $request->validated('q');

        $filtered = BudgetArticle::query()
            ->where('budget_id', $budget->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($amendsLaw, fn ($query) => $query->whereNotNull('law_title'))
            ->when($terms, fn ($query, string $terms) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$terms}%")
                ->orWhere('number', 'like', "%{$terms}%")
                ->orWhere('law_subtitle', 'like', "%{$terms}%")));

        $articles = (clone $filtered)
            ->withCount('proposals')
            ->orderBy('position')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $cells = $matrix->cells($filtered);
        $parties = $matrix->votingParties($cells, $this->budgetStats->partyOrder($budget)->all());
        $presentStatuses = BudgetArticle::query()->where('budget_id', $budget->id)->distinct()->toBase()->pluck('status')->all();

        return view('pages.budgets.articles.index', [
            'budget' => $budget,
            'articles' => $articles,
            'cells' => $cells,
            'totals' => $matrix->totals($cells, $parties),
            'parties' => $parties,
            'statuses' => BudgetProposalStatus::listable($presentStatuses),
            'filters' => [
                'estado' => $status?->value,
                'lei' => $amendsLaw,
                'q' => $terms,
            ],
        ]);
    }

    public function show(Request $request): View
    {
        $budget = $this->resolver->budget($request->route('year'), 'budgets.articles.show', ['article' => $request->route('article')]);
        $article = $this->resolver->article($budget, $request->route('article'), 'budgets.articles.show');
        $article->load([
            'votes.positions',
            'proposals' => fn ($query) => $query->with('parties')->orderByRaw('cast(number as unsigned)')->orderBy('number'),
        ]);

        return view('pages.budgets.articles.show', [
            'budget' => $budget,
            'article' => $article,
            'parties' => $this->budgetStats->partyOrder($budget)->all(),
        ]);
    }
}
