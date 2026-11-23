<?php

namespace App\Console\Commands;

use App\Models\Budget;
use App\Services\BudgetProposalSyncService;
use App\Services\LegislatureContext;
use Illuminate\Console\Command;

class RegisterBudget extends Command
{
    protected $signature = 'ar:budget:register
        {year : Year of the State Budget, e.g. 2027}
        {--legislature= : Sigla of the legislature that votes it (default: the current one)}
        {--proposals-url= : URL of the OEPropostasAlteracao<year>Or_json.txt file on parlamento.pt}
        {--budget-url= : URL of the OE<year>Or_json.txt file on parlamento.pt}';

    protected $description = 'Register a State Budget, or update its source URLs';

    public function handle(LegislatureContext $legislatureContext, BudgetProposalSyncService $syncService): int
    {
        $year = $this->argument('year');

        if (! ctype_digit($year) || (int) $year < 1976 || (int) $year > 2100) {
            $this->error("[{$year}] is not a budget year.");

            return self::FAILURE;
        }

        $sigla = $this->option('legislature');
        $legislature = $legislatureContext->resolve($sigla);

        if (! $legislature) {
            $this->error($sigla ? "Unknown legislature [{$sigla}]." : 'No legislature registered yet: run ar:legislature:register first.');

            return self::FAILURE;
        }

        $budget = Budget::firstOrNew(['year' => (int) $year, 'kind' => Budget::KIND_ORDINARY]);

        if ($budget->exists && $budget->legislature_id !== $legislature->id) {
            $this->error("The {$year} budget is already registered in another legislature.");

            return self::FAILURE;
        }

        $budget->legislature_id = $legislature->id;
        $budget->fill(array_filter([
            'proposals_url' => $this->option('proposals-url'),
            'budget_url' => $this->option('budget-url'),
        ]));
        $budget->initiative_id ??= $syncService->findInitiativeId($budget);
        $budget->save();

        $this->info(($budget->wasRecentlyCreated ? 'Registered' : 'Updated')." the {$year} budget of the {$legislature->sigla} legislature.");

        if (! $budget->initiative_id) {
            $this->warn("No initiative \"Orçamento do Estado para {$year}\" found yet: it is linked on the next import.");
        }

        if (! $budget->proposals_url) {
            $this->warn('No proposals URL yet: run again with --proposals-url before importing.');
        }

        return self::SUCCESS;
    }
}
