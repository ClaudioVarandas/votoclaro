<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SelectsBudgets;
use App\Console\Commands\Concerns\ShowsImportProgress;
use App\Services\BudgetProposalSyncService;
use App\Services\BudgetTopicService;
use App\Services\LegislatureContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportBudgetProposals extends Command
{
    use SelectsBudgets;
    use ShowsImportProgress;

    protected $signature = 'ar:budget:import {--year= : Year of the State Budget (default: every budget of the current legislature)}';

    protected $description = 'Import the amendments proposed to the State Budget and how each party voted them';

    public function handle(LegislatureContext $legislatureContext, BudgetProposalSyncService $syncService, BudgetTopicService $topicService): int
    {
        $budgets = $this->budgets($legislatureContext);

        if ($budgets->isEmpty()) {
            $this->error($this->noBudgetMessage());

            return self::FAILURE;
        }

        $failed = false;

        foreach ($budgets as $budget) {
            $this->info("Downloading the amendments to the {$budget->year} budget...");

            try {
                $imported = $syncService->sync($budget, $this->onDownloaded('amendments'), $this->onImported());

                $this->finishImportProgress();
                $this->info("Sync completed: {$imported} amendments imported.");
                $this->info('Topics found: '.$topicService->rebuild($budget).'.');
            } catch (\Throwable $e) {
                $this->abortImportProgress();
                $this->error($e->getMessage());
                $failed = true;
            }
        }

        Artisan::call('cache:clear');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
