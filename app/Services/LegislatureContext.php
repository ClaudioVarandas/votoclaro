<?php

namespace App\Services;

use App\Models\Legislature;
use Illuminate\Support\Collection;

/**
 * Which legislature the current request shows. Pages of the current legislature live at
 * unprefixed URLs (/partidos); earlier ones under /legislatura/{sigla} (/legislatura/XVI/partidos),
 * with the same route names prefixed by "legislatures.".
 */
class LegislatureContext
{
    private ?Legislature $current = null;

    private bool $currentResolved = false;

    /** @var Collection<int, Legislature>|null */
    private ?Collection $all = null;

    public function current(): ?Legislature
    {
        if (! $this->currentResolved) {
            $this->current = Legislature::query()->orderByDesc('number')->first();
            $this->currentResolved = true;
        }

        return $this->current;
    }

    /**
     * The legislature with this sigla (any case), or the current one when none is given.
     */
    public function resolve(?string $sigla): ?Legislature
    {
        if ($sigla === null || $sigla === '') {
            return $this->current();
        }

        return Legislature::query()->where('sigla', strtoupper($sigla))->first();
    }

    public function active(): ?Legislature
    {
        return $this->bound() ?? $this->current();
    }

    public function activeOrFail(): Legislature
    {
        return $this->active() ?? abort(404);
    }

    public function isViewingCurrent(): bool
    {
        $bound = $this->bound();

        return $bound === null || $bound->is($this->current());
    }

    /**
     * @return Collection<int, Legislature>
     */
    public function all(): Collection
    {
        return $this->all ??= Legislature::query()->with('parties')->orderByDesc('number')->get();
    }

    /**
     * URL of a legislature-scoped page, for the given legislature or the one being viewed.
     */
    public function route(string $name, mixed $parameters = [], ?Legislature $legislature = null): string
    {
        $legislature ??= $this->bound();

        if ($legislature === null || $legislature->is($this->current())) {
            return route($name, $parameters);
        }

        return route("legislatures.{$name}", ['legislature' => $legislature->sigla, ...(array) $parameters]);
    }

    /**
     * The legislature named in the URL; null on the unprefixed pages of the current one.
     */
    private function bound(): ?Legislature
    {
        $bound = request()->route('legislature');

        return $bound instanceof Legislature ? $bound : null;
    }
}
