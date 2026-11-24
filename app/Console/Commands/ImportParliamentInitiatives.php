<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ShowsImportProgress;
use App\Services\LegislatureContext;
use App\Services\ParliamentInitiativeSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportParliamentInitiatives extends Command
{
    use ShowsImportProgress;

    protected $signature = 'ar:initiatives:import {--legislature= : Sigla of the legislature, e.g. XVII (default: the current one)}';

    protected $description = 'Import parliamentary initiatives from JSON source';

    public function handle(LegislatureContext $legislatureContext, ParliamentInitiativeSyncService $syncService): int
    {
        $this->announceImport();
        $sigla = $this->option('legislature');
        $legislature = $legislatureContext->resolve($sigla);

        if (! $legislature) {
            $this->error($sigla ? "Unknown legislature [{$sigla}]." : 'No legislature registered yet: run ar:legislature:register first.');

            return self::FAILURE;
        }

        $this->info("Downloading the initiatives of the {$legislature->sigla} legislature...");

        try {
            $imported = $syncService->sync($legislature, $this->onDownloaded('initiatives'), $this->onImported());

            $this->finishImportProgress();

            Artisan::call('cache:clear');

            $this->info("Sync completed: {$imported} initiatives imported.");
        } catch (\Throwable $e) {
            $this->abortImportProgress();
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
