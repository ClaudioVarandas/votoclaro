<?php

namespace App\View\Components;

use App\Models\Legislature;
use App\Services\LegislatureContext;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LegislatureSwitcher extends Component
{
    /** @var list<string> */
    private const SCOPED_PAGES = [
        'dashboard',
        'initiatives.index',
        'budgets.index',
        'budgets.show',
        'budgets.proposals.index',
        'budgets.proposals.show',
        'budgets.topics.show',
        'budgets.articles.index',
        'budgets.articles.show',
        'parties.index',
        'parties.show',
    ];

    /**
     * A budget belongs to one legislature, so its pages lead to the budget list of the others.
     *
     * @var list<string>
     */
    private const BUDGET_PAGES = [
        'budgets.show',
        'budgets.proposals.index',
        'budgets.proposals.show',
        'budgets.topics.show',
        'budgets.articles.index',
        'budgets.articles.show',
    ];

    public function __construct(private LegislatureContext $legislatureContext) {}

    public function shouldRender(): bool
    {
        return $this->page() !== null
            && ($this->legislatureContext->all()->count() > 1 || ! $this->legislatureContext->isViewingCurrent());
    }

    public function active(): ?Legislature
    {
        return $this->legislatureContext->active();
    }

    public function isViewingCurrent(): bool
    {
        return $this->legislatureContext->isViewingCurrent();
    }

    /**
     * The same page in every legislature. A party page falls back to the party list of a
     * legislature in which that party had no parliamentary group.
     *
     * @return list<array{label: string, url: string, selected: bool}>
     */
    public function options(): array
    {
        $page = $this->page();
        $active = $this->active();

        return $this->legislatureContext->all()
            ->map(fn (Legislature $legislature): array => [
                'label' => $legislature->isClosed()
                    ? __('ui.legislature.name', ['sigla' => $legislature->sigla])
                    : __('ui.legislature.name_ongoing', ['sigla' => $legislature->sigla]),
                'url' => $this->urlFor($legislature, $page),
                'selected' => $legislature->is($active),
            ])
            ->all();
    }

    public function render(): View|Closure|string
    {
        return view('components.legislature-switcher');
    }

    private function page(): ?string
    {
        $page = str((string) request()->route()?->getName())->after('legislatures.')->toString();

        return in_array($page, self::SCOPED_PAGES, true) ? $page : null;
    }

    private function urlFor(Legislature $legislature, string $page): string
    {
        if (in_array($page, self::BUDGET_PAGES, true)) {
            return $legislature->is($this->active())
                ? request()->url()
                : $this->legislatureContext->route('budgets.index', [], $legislature);
        }

        if ($page !== 'parties.show') {
            return $this->legislatureContext->route($page, [], $legislature);
        }

        $acronym = strtoupper((string) request()->route('party'));

        if (! $legislature->parties->contains('acronym', $acronym)) {
            return $this->legislatureContext->route('parties.index', [], $legislature);
        }

        return $this->legislatureContext->route('parties.show', strtolower($acronym), $legislature);
    }
}
