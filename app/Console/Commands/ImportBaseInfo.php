<?php

namespace App\Console\Commands;

use App\Models\Party;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ImportBaseInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ar:base_info:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = config('services.parliament.base_info_url');

        $response = Http::get($url);
        $data = $response->json();

        if (app()->isLocal()) {
            File::ensureDirectoryExists(base_path('data'));
            File::put(base_path('data/base_info.json'), $response->body());
            $this->info('Saved raw response to data/base_info.json');
        }

        foreach ($data['GruposParlamentares'] as $group) {
            Party::updateOrCreate(
                ['acronym' => $group['sigla']],
                ['name' => $group['nome']]
            );
        }

        $this->info('Parties synced.');
    }
}
