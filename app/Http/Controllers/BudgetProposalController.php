<?php

namespace App\Http\Controllers;

use App\Enums\BudgetProposalStatus;
use App\Http\Requests\IndexBudgetProposalsRequest;
use App\Models\Budget;
use App\Models\BudgetProposal;
use App\Models\BudgetTheme;
use App\Models\BudgetTopic;
use App\Services\BudgetMatrixService;
use App\Services\BudgetResolver;
use App\Services\BudgetStatsService;
use App\Services\LegislatureContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BudgetProposalController extends Controller
{
    private const PER_PAGE = 50;

    public function __construct(
        private BudgetResolver $resolver,
        private BudgetStatsService $budgetStats,
        private LegislatureContext $legislatureContext,
    ) {}

    public function index(IndexBudgetProposalsRequest $request, BudgetMatrixService $matrix): View
    {
        $topicSlug = $request->route('topic');
        $budget = $topicSlug !== null
            ? $this->resolver->budget($request->route('year'), 'budgets.topics.show', ['topic' => $topicSlug])
            : $this->resolver->budget($request->route('year'), 'budgets.proposals.index');
        $topic = $topicSlug !== null ? $budget->topics()->where('slug', $topicSlug)->firstOrFail() : null;
        $themes = $this->budgetStats->themes($budget);
        $theme = $themes->firstWhere('slug', $request->validated('tema'));
        $party = strtoupper((string) $request->validated('partido')) ?: null;
        $status = BudgetProposalStatus::tryFrom((string) $request->validated('estado'));
        $government = $this->budgetStats->governmentOf($budget);
        $againstGovernment = $government !== [] && $request->boolean('contra_governo');
        $apart = $this->votedApartPair($budget, (string) $request->validated('divergem'));

        $filtered = BudgetProposal::query()
            ->where('budget_id', $budget->id)
            ->when($topic, fn ($query) => $query->whereRelation('topics', 'budget_topics.id', $topic->id))
            ->when($theme, fn ($query) => $query->whereRelation('themes', 'budget_themes.id', $theme->id))
            ->when($party, fn ($query) => $query->whereRelation('parties', 'acronym', $party))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($againstGovernment, fn ($query) => $query->approvedAgainstGovernment($government))
            ->when($apart, fn ($query) => $query->votedApart(...$apart))
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
            'heading' => $this->heading($budget, $topic, $theme),
            'baseUrl' => $topic !== null
                ? $this->legislatureContext->route('budgets.topics.show', ['year' => $budget->year, 'topic' => $topic->slug], $budget->legislature)
                : $this->legislatureContext->route('budgets.proposals.index', ['year' => $budget->year], $budget->legislature),
            'proposingParties' => collect($this->budgetStats->parties($budget))->where('proposed', '>', 0)->pluck('acronym')->all(),
            'statuses' => BudgetProposalStatus::listable($presentStatuses),
            'government' => $government,
            'filters' => [
                'tema' => $theme?->slug,
                'partido' => $party,
                'estado' => $status?->value,
                'q' => $request->validated('q'),
                'contra_governo' => $againstGovernment ? '1' : null,
                'divergem' => $apart ? strtolower(implode(',', $apart)) : null,
            ],
            'apart' => $apart,
        ]);
    }

    public function show(Request $request): View
    {
        $budget = $this->resolver->budget($request->route('year'), 'budgets.proposals.show', ['proposal' => $request->route('proposal')]);
        $proposal = $this->resolver->proposal($budget, $request->route('proposal'), 'budgets.proposals.show');
        $proposal->load(['parties', 'themes', 'votes.positions', 'articles']);

        return view('pages.budgets.proposals.show', [
            'budget' => $budget,
            'proposal' => $proposal,
            'parties' => $this->budgetStats->partyOrder($budget)->all(),
        ]);
    }

    /**
     * The two parties named in "divergem" (as "ps,ch"), when both voted this budget and they
     * differ; null otherwise, which leaves the filter off.
     *
     * @return array{0: string, 1: string}|null
     */
    private function votedApartPair(Budget $budget, string $named): ?array
    {
        $pair = array_values(array_unique(array_filter(array_map(fn (string $acronym): string => strtoupper(trim($acronym)), explode(',', $named)))));

        if (count($pair) !== 2 || array_diff($pair, $this->budgetStats->agreement($budget)['parties']) !== []) {
            return null;
        }

        return [$pair[0], $pair[1]];
    }

    /**
     * The title of a matrix narrowed to a topic or a theme, the topic first; null for the full one.
     *
     * @return array{eyebrow: string, title: string, subtitle: string}|null
     */
    private function heading(Budget $budget, ?BudgetTopic $topic, ?BudgetTheme $theme): ?array
    {
        $budgetName = __('ui.budget.name', ['year' => $budget->year]);

        return match (true) {
            $topic !== null => [
                'eyebrow' => __('ui.budget.topics.eyebrow'),
                'title' => $topic->label,
                'subtitle' => trans_choice('ui.budget.topics.subtitle', $topic->proposals_count, [
                    'count' => number_format($topic->proposals_count, 0, ',', '.'),
                    'parties' => $topic->parties_count,
                    'budget' => $budgetName,
                ]),
            ],
            $theme !== null => [
                'eyebrow' => __('ui.budget.matrix.theme'),
                'title' => $theme->name,
                'subtitle' => trans_choice('ui.budget.matrix.theme_subtitle', $theme->proposals_count, [
                    'count' => number_format($theme->proposals_count, 0, ',', '.'),
                    'budget' => $budgetName,
                ]),
            ],
            default => null,
        };
    }
}
