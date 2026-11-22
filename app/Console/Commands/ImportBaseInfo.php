<?php

namespace App\Console\Commands;

use App\Models\Party;
use App\Services\LegislatureContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ImportBaseInfo extends Command
{
    protected $signature = 'ar:base_info:import {--legislature= : Sigla of the legislature, e.g. XVII (default: the current one)}';

    protected $description = "Import a legislature's dates and parliamentary groups from its base information";

    public function handle(LegislatureContext $legislatureContext): int
    {
        $sigla = $this->option('legislature');
        $legislature = $legislatureContext->resolve($sigla);

        if (! $legislature) {
            $this->error($sigla ? "Unknown legislature [{$sigla}]." : 'No legislature registered yet: run ar:legislature:register first.');

            return self::FAILURE;
        }

        if (! $legislature->base_info_url) {
            $this->error("The {$legislature->sigla} legislature has no base information URL.");

            return self::FAILURE;
        }

        $response = Http::get($legislature->base_info_url);

        if (! $response->successful()) {
            $this->error('Failed to fetch the base information JSON.');

            return self::FAILURE;
        }

        $data = $response->json();

        if (app()->isLocal()) {
            File::ensureDirectoryExists(base_path("data/{$legislature->sigla}"));
            File::put(base_path("data/{$legislature->sigla}/base_info.json"), $response->body());
            $this->info("Saved raw response to data/{$legislature->sigla}/base_info.json");
        }

        $details = $data['DetalheLegislatura'] ?? [];

        if (($details['sigla'] ?? null) !== $legislature->sigla) {
            $this->error('The base information is for legislature ['.($details['sigla'] ?? '?')."], not {$legislature->sigla}.");

            return self::FAILURE;
        }

        $legislature->update([
            'start_date' => $details['dtini'] ?? null,
            'end_date' => $details['dtfim'] ?? null,
        ]);

        $partyIds = collect($data['GruposParlamentares'] ?? [])
            ->map(fn (array $group): int => Party::updateOrCreate(
                ['acronym' => $group['sigla']],
                ['name' => $group['nome']],
            )->id);

        $legislature->parties()->syncWithoutDetaching($partyIds);

        $this->info("{$legislature->sigla} legislature and its {$partyIds->count()} parties synced.");

        return self::SUCCESS;
    }
}
