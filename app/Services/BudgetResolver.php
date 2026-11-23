<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetProposal;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Finds the budget and the amendment a URL names, and sends a URL that names them in another way
 * permanently to their canonical address.
 */
class BudgetResolver
{
    public function __construct(private LegislatureContext $legislatureContext) {}

    /**
     * A budget belongs to the legislature that voted it: asked for under another one, it is sent
     * to its own.
     *
     * @param  array<string, mixed>  $parameters  of the route, besides the year
     */
    public function budget(string $year, string $routeName, array $parameters = []): Budget
    {
        $legislature = $this->legislatureContext->activeOrFail();
        $budget = Budget::query()
            ->with('legislature')
            ->where('year', (int) $year)
            ->where('kind', Budget::KIND_ORDINARY)
            ->firstOrFail();

        if ($budget->legislature_id !== $legislature->id) {
            $this->redirect($this->legislatureContext->route($routeName, ['year' => $budget->year, ...$parameters], $budget->legislature));
        }

        return $budget;
    }

    /**
     * By its slug; by its number when the rest of the slug is outdated or missing, as in
     * /propostas/1907C, which is sent to the full address.
     */
    public function proposal(Budget $budget, string $slug, string $routeName): BudgetProposal
    {
        $slug = strtolower($slug);
        $proposals = BudgetProposal::query()->where('budget_id', $budget->id);

        if ($proposal = (clone $proposals)->where('slug', $slug)->first()) {
            return $proposal;
        }

        $segments = explode('-', $slug);
        $numbers = array_filter([
            isset($segments[1]) ? $segments[0].'-'.$segments[1] : null,
            $segments[0],
        ]);

        $proposal = (clone $proposals)
            ->whereIn('number', array_map('strtoupper', $numbers))
            ->get()
            ->sortByDesc(fn (BudgetProposal $proposal): int => strlen($proposal->number))
            ->first();

        abort_if($proposal === null, Response::HTTP_NOT_FOUND);

        $this->redirect($this->legislatureContext->route($routeName, ['year' => $budget->year, 'proposal' => $proposal->slug], $budget->legislature));
    }

    private function redirect(string $url): never
    {
        $query = request()->getQueryString();

        throw new HttpResponseException(redirect($query ? "{$url}?{$query}" : $url, Response::HTTP_MOVED_PERMANENTLY));
    }
}
