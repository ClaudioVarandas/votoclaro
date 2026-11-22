<?php

namespace App\Console\Commands;

use App\Models\Legislature;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RegisterLegislature extends Command
{
    protected $signature = 'ar:legislature:register
        {sigla : Roman numeral of the legislature, e.g. XVIII}
        {--initiatives-url= : URL of the Iniciativas<sigla>_json.txt file on parlamento.pt}
        {--base-info-url= : URL of the InformacaoBase<sigla>_json.txt file on parlamento.pt}';

    protected $description = 'Register a legislature, or update its source URLs, then import its base information';

    public function handle(): int
    {
        $sigla = strtoupper($this->argument('sigla'));

        try {
            $number = Legislature::numberFromSigla($sigla);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $legislature = Legislature::firstOrNew(['sigla' => $sigla], ['number' => $number]);

        $legislature->fill(array_filter([
            'initiatives_url' => $this->option('initiatives-url'),
            'base_info_url' => $this->option('base-info-url'),
        ]))->save();

        $this->info($legislature->wasRecentlyCreated
            ? "{$sigla} legislature registered."
            : "{$sigla} legislature updated.");

        if (! $legislature->base_info_url) {
            $this->warn('No base information URL yet: dates and parties are not imported.');

            return self::SUCCESS;
        }

        return $this->call('ar:base_info:import', ['--legislature' => $sigla]);
    }
}
