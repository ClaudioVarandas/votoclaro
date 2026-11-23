<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Number;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * The Parliament's files are read as a stream, so the total number of items is only known at the
 * end: the bar measures the bytes parsed and shows the count imported so far.
 */
trait ShowsImportProgress
{
    private ?ProgressBar $importProgressBar = null;

    private function onDownloaded(string $items): \Closure
    {
        return function (int $fileSize) use ($items): void {
            $this->line('Downloaded '.Number::fileSize($fileSize, precision: 1).'. Importing...');

            $this->importProgressBar = $this->output->createProgressBar($fileSize);
            $this->importProgressBar->setFormat(" %percent:3s%% [%bar%] %message% {$items}, %elapsed:6s%");
            $this->importProgressBar->setMessage('0');
            $this->importProgressBar->start();
        };
    }

    private function onImported(): \Closure
    {
        return function (int $bytesRead, int $imported): void {
            $this->importProgressBar?->setMessage((string) $imported);
            $this->importProgressBar?->setProgress($bytesRead);
        };
    }

    private function finishImportProgress(): void
    {
        $this->importProgressBar?->finish();
        $this->importProgressBar = null;
        $this->newLine();
    }

    private function abortImportProgress(): void
    {
        $this->importProgressBar?->clear();
        $this->importProgressBar = null;
    }
}
