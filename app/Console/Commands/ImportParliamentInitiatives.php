<?php

namespace App\Console\Commands;

use App\Services\LegislatureContext;
use App\Services\ParliamentInitiativeSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportParliamentInitiatives extends Command
{
    protected $signature = 'ar:initiatives:import {--legislature= : Sigla of the legislature, e.g. XVII (default: the current one)}';

    protected $description = 'Import parliamentary initiatives from JSON source';

    public function handle(LegislatureContext $legislatureContext, ParliamentInitiativeSyncService $syncService): int
    {
        $sigla = $this->option('legislature');
        $legislature = $legislatureContext->resolve($sigla);

        if (! $legislature) {
            $this->error($sigla ? "Unknown legislature [{$sigla}]." : 'No legislature registered yet: run ar:legislature:register first.');

            return self::FAILURE;
        }

        $this->info("Starting sync of the {$legislature->sigla} legislature...");

        try {
            $syncService->sync($legislature);

            Artisan::call('cache:clear');

            $this->info('Sync completed successfully.');

        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
