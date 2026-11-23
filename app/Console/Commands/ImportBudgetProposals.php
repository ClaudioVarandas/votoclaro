<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ShowsImportProgress;
use App\Models\Budget;
use App\Services\BudgetProposalSyncService;
use App\Services\LegislatureContext;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class ImportBudgetProposals extends Command
{
    use ShowsImportProgress;

    protected $signature = 'ar:budget:import {--year= : Year of the State Budget (default: every budget of the current legislature)}';

    protected $description = 'Import the amendments proposed to the State Budget and how each party voted them';

    public function handle(LegislatureContext $legislatureContext, BudgetProposalSyncService $syncService): int
    {
        $budgets = $this->budgets($legislatureContext);

        if ($budgets->isEmpty()) {
            $this->error($this->option('year')
                ? "No {$this->option('year')} budget registered: run ar:budget:register first."
                : 'No budget registered in the current legislature: run ar:budget:register first.');

            return self::FAILURE;
        }

        $failed = false;

        foreach ($budgets as $budget) {
            $this->info("Downloading the amendments to the {$budget->year} budget...");

            try {
                $imported = $syncService->sync($budget, $this->onDownloaded('amendments'), $this->onImported());

                $this->finishImportProgress();
                $this->info("Sync completed: {$imported} amendments imported.");
            } catch (\Throwable $e) {
                $this->abortImportProgress();
                $this->error($e->getMessage());
                $failed = true;
            }
        }

        Artisan::call('cache:clear');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return Collection<int, Budget>
     */
    private function budgets(LegislatureContext $legislatureContext): Collection
    {
        if ($this->option('year')) {
            return Budget::query()->where('year', (int) $this->option('year'))->get();
        }

        return Budget::query()
            ->where('legislature_id', $legislatureContext->current()?->id)
            ->whereNotNull('proposals_url')
            ->orderBy('year')
            ->get();
    }
}
