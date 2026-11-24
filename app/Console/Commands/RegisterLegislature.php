<?php

namespace App\Console\Commands;

use App\Models\Legislature;
use App\Models\Party;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class RegisterLegislature extends Command
{
    protected $signature = 'ar:legislature:register
        {sigla : Roman numeral of the legislature, e.g. XVIII}
        {--initiatives-url= : URL of the Iniciativas<sigla>_json.txt file on parlamento.pt}
        {--base-info-url= : URL of the InformacaoBase<sigla>_json.txt file on parlamento.pt}
        {--government= : Acronyms of the parties in government, comma-separated, e.g. PSD,CDS-PP}';

    protected $description = 'Register a legislature, or update its source URLs or its government, then import its base information';

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

        $status = self::SUCCESS;

        if ($legislature->base_info_url) {
            $status = $this->call('ar:base_info:import', ['--legislature' => $sigla]);
        } else {
            $this->warn('No base information URL yet: dates and parties are not imported.');
        }

        if ($this->option('government') !== null && ! $this->markGovernment($legislature, (string) $this->option('government'))) {
            return self::FAILURE;
        }

        return $status;
    }

    /**
     * Replaces the government of the legislature with the parties named, after the base
     * information has attached the legislature's parties. Nothing changes when one is unknown.
     * The budget figures counted against the government are cached, so the cache is cleared.
     */
    private function markGovernment(Legislature $legislature, string $acronyms): bool
    {
        $named = collect(explode(',', $acronyms))->map(fn (string $acronym): string => strtoupper(trim($acronym)))->filter()->unique()->values();
        $parties = Party::query()->whereIn('acronym', $named)->pluck('id', 'acronym');
        $unknown = $named->diff($parties->keys());

        if ($named->isEmpty() || $unknown->isNotEmpty()) {
            $this->error($named->isEmpty() ? 'Name at least one party in --government.' : 'Unknown party: '.$unknown->implode(', ').'.');

            return false;
        }

        $governmentIds = $parties->values()->all();
        $legislature->parties()->syncWithoutDetaching($governmentIds);

        $otherIds = array_diff($legislature->parties()->pluck('parties.id')->all(), $governmentIds);

        if ($otherIds !== []) {
            $legislature->parties()->updateExistingPivot($otherIds, ['is_government' => false]);
        }

        $legislature->parties()->updateExistingPivot($governmentIds, ['is_government' => true]);

        Artisan::call('cache:clear');

        $this->info("Government of the {$legislature->sigla} legislature: {$named->implode(', ')}.");

        return true;
    }
}
