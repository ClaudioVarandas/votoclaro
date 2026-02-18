<?php

namespace App\Console\Commands;

use App\Services\ParliamentInitiativeSyncService;
use Illuminate\Console\Command;

class ImportParliamentInitiatives extends Command
{
    protected $signature = 'ar:initiatives:import';
    protected $description = 'Import parliamentary initiatives from JSON source';

    public function handle()
    {
        $this->info('Starting sync...');

        try {
            app(ParliamentInitiativeSyncService::class)->sync();

            $this->info('Sync completed successfully.');

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
    }
}
