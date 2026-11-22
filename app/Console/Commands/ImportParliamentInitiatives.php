<?php

namespace App\Console\Commands;

use App\Services\LegislatureContext;
use App\Services\ParliamentInitiativeSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Number;
use Symfony\Component\Console\Helper\ProgressBar;

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

        $this->info("Downloading the initiatives of the {$legislature->sigla} legislature...");
        $progressBar = null;

        try {
            $imported = $syncService->sync(
                $legislature,
                function (int $fileSize) use (&$progressBar): void {
                    $this->line('Downloaded '.Number::fileSize($fileSize, precision: 1).'. Importing...');
                    $progressBar = $this->startProgressBar($fileSize);
                },
                function (int $bytesRead, int $imported) use (&$progressBar): void {
                    $progressBar->setMessage((string) $imported);
                    $progressBar->setProgress($bytesRead);
                },
            );

            $progressBar->finish();
            $this->newLine();

            Artisan::call('cache:clear');

            $this->info("Sync completed: {$imported} initiatives imported.");
        } catch (\Throwable $e) {
            $progressBar?->clear();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The total number of initiatives is only known once the file is read, so the bar measures
     * the bytes parsed and shows the count imported so far.
     */
    private function startProgressBar(int $fileSize): ProgressBar
    {
        $progressBar = $this->output->createProgressBar($fileSize);
        $progressBar->setFormat(' %percent:3s%% [%bar%] %message% initiatives, %elapsed:6s%');
        $progressBar->setMessage('0');
        $progressBar->start();

        return $progressBar;
    }
}
