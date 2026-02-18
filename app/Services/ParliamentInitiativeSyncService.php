<?php

namespace App\Services;

use App\Models\Initiative;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ParliamentInitiativeSyncService
{
    public function __construct(private VoteParser $voteParser) {}

    public function sync(): void
    {
        $url = config('services.parliament.json_url');

        $response = Http::get($url);

        if (! $response->successful()) {
            throw new Exception('Failed to fetch parliamentary JSON');
        }

        $data = $response->json();

        if (app()->isLocal()) {
            $dir = base_path('data/initiatives');
            File::ensureDirectoryExists($dir);
        }

        foreach ($data as $item) {
            if (app()->isLocal()) {
                File::put($dir.'/'.$item['IniId'].'.json', json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            DB::transaction(function () use ($item) {
                $this->syncInitiative($item);
            });
        }
    }

    private function syncInitiative(array $item): void
    {
        $initiativeId = $item['IniId'];

        $entryDate = $this->extractEntryDate($item['IniEventos'] ?? []);
        $latestVote = $this->extractLatestVote($item['IniEventos'] ?? []);
        $author = $this->detectAuthor($item);

        $status = 'in_progress';
        $finalVoteDate = null;
        $daysToApproval = null;

        if ($latestVote) {

            $finalVoteDate = Carbon::parse($latestVote['data']);

            $status = match (strtolower($latestVote['resultado'])) {
                'aprovado' => 'approved',
                'rejeitado' => 'rejected',
                default => 'in_progress',
            };

            $daysToApproval = $entryDate?->diffInDays($finalVoteDate);
        }

        $initiative = Initiative::updateOrCreate(
            ['id' => $initiativeId],
            [
                'title' => $item['IniTitulo'],
                // 'author_type' => $author['category'],
                'status' => $status,
                'entry_date' => $entryDate,
                'final_vote_date' => $finalVoteDate,
                'days_to_approval' => $daysToApproval,
                'author_category' => $author['category'],
                'author_party' => $author['party'],
                'author_label' => $author['label'],
                'initiative_type' => $item['IniTipo'],
                'initiative_type_label' => $item['IniDescTipo'],
                'last_synced_at' => now(),
            ]
        );

        $this->syncVotes($initiative, $item['IniEventos'] ?? []);
    }

    private function extractLatestVote(array $events): ?array
    {
        $votes = [];

        foreach ($events as $event) {
            if (! empty($event['Votacao'])) {
                foreach ($event['Votacao'] as $vote) {
                    $votes[] = $vote;
                }
            }
        }

        if (empty($votes)) {
            return null;
        }

        usort($votes, function ($a, $b) {
            return Carbon::parse($b['data'])
                ->timestamp <=> Carbon::parse($a['data'])->timestamp;
        });

        return $votes[0];
    }

    private function syncVotes(Initiative $initiative, array $events): void
    {
        $initiative->votes()->delete();

        foreach ($events as $event) {

            if (empty($event['Votacao'])) {
                continue;
            }

            foreach ($event['Votacao'] as $voteData) {

                $vote = $initiative->votes()->create([
                    'id' => $voteData['id'],
                    'date' => $voteData['data'],
                    'result' => $voteData['resultado'],
                    'unanimous' => $voteData['unanime'] === 'unanime',
                    'is_latest' => false,
                ]);

                if (! empty($voteData['detalhe'])) {
                    $positions = $this->voteParser->parse($voteData['detalhe']);

                    foreach ($positions as $party => $position) {
                        $vote->positions()->create([
                            'party' => $party,
                            'position' => $position,
                        ]);
                    }
                }
            }
        }

        // 🔥 Mark latest vote via DB
        $latestVote = $initiative->votes()
            ->orderByDesc('date')
            ->first();

        if ($latestVote) {
            $initiative->votes()->update(['is_latest' => false]);
            $latestVote->update(['is_latest' => true]);
        }
    }

    private function extractEntryDate(array $events): ?Carbon
    {
        $entrada = null;
        $earliest = null;

        foreach ($events as $event) {

            if (! empty($event['DataFase'])) {

                $date = Carbon::parse($event['DataFase']);

                if (! $earliest || $date->lt($earliest)) {
                    $earliest = $date;
                }

                if (
                    ($event['CodigoFase'] ?? null) === '10' ||
                    ($event['Fase'] ?? null) === 'Entrada'
                ) {
                    $entrada = $date;
                }
            }
        }

        return $entrada ?? $earliest;
    }

    private function detectAuthor(array $item): array
    {
        // 1. Government
        if (! empty($item['IniAutorOutros']['nome']) &&
            $item['IniAutorOutros']['nome'] === 'Governo') {
            return [
                'category' => 'government',
                'party' => null,
                'label' => 'Governo',
            ];
        }

        // 2. Parliamentary Group
        if (! empty($item['IniAutorGruposParlamentares'])) {

            $gp = $item['IniAutorGruposParlamentares'][0]['GP'] ?? null;

            return [
                'category' => 'parliamentary_group',
                'party' => $gp,
                'label' => "Grupo Parlamentar {$gp}",
            ];
        }

        // 3. Deputies (individual MPs)
        if (! empty($item['IniAutorDeputados'])) {

            $parties = collect($item['IniAutorDeputados'])
                ->pluck('GP')
                ->unique()
                ->filter();

            if ($parties->count() > 1) {
                return [
                    'category' => 'mixed',
                    'party' => null,
                    'label' => 'Iniciativa multipartidária',
                ];
            }

            $gp = $parties->first();

            return [
                'category' => 'deputies',
                'party' => $gp,
                'label' => "Deputados ({$gp})",
            ];
        }

        $parties = collect($item['IniAutorDeputados'] ?? [])
            ->pluck('GP')
            ->unique()
            ->filter();

        if ($parties->count() > 1) {
            return [
                'category' => 'mixed',
                'party' => null,
                'label' => 'Iniciativa multipartidária',
            ];
        }

        return [
            'category' => 'other',
            'party' => null,
            'label' => 'Outros',
        ];
    }
}
