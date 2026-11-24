<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Number;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * The Parliament's files are read as a stream, so the total number of items is only known at the
 * end: the bar measures the bytes parsed and shows the count imported so far. Only in a terminal:
 * written to the scheduler's log, every redraw would be a line of its own.
 */
trait ShowsImportProgress
{
    private ?ProgressBar $importProgressBar = null;

    private function onDownloaded(string $items): \Closure
    {
        return function (int $fileSize) use ($items): void {
            $this->line('Downloaded '.Number::fileSize($fileSize, precision: 1).'. Importing...');

            if (! $this->output->isDecorated()) {
                return;
            }

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
        if ($this->importProgressBar === null) {
            return;
        }

        $this->importProgressBar->finish();
        $this->importProgressBar = null;
        $this->newLine();
    }

    /**
     * The first line of a run, so the scheduler's log tells the runs apart.
     */
    private function announceImport(): void
    {
        $this->line('['.now()->toDateTimeString().'] '.$this->getName());
    }

    private function abortImportProgress(): void
    {
        $this->importProgressBar?->clear();
        $this->importProgressBar = null;
    }
}
