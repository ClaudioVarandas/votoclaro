<?php

namespace App\Services;

use App\Enums\InitiativeStatus;
use App\Models\Initiative;
use App\Models\Legislature;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

class ParliamentInitiativeSyncService
{
    private const PHASE_WITHDRAWN = '648';

    private const PHASE_LAPSED = '649';

    private const DOWNLOAD_TIMEOUT_SECONDS = 600;

    public function __construct(private VoteParser $voteParser) {}

    /**
     * The file of a legislature grows past 100 MB, more than json_decode fits in memory, so it is
     * downloaded to disk and read one initiative at a time.
     */
    public function sync(Legislature $legislature): void
    {
        if (! $legislature->initiatives_url) {
            throw new Exception("The {$legislature->sigla} legislature has no initiatives URL.");
        }

        $path = tempnam(sys_get_temp_dir(), 'ar-initiatives-');

        try {
            $response = Http::sink($path)
                ->timeout(self::DOWNLOAD_TIMEOUT_SECONDS)
                ->get($legislature->initiatives_url);

            if (! $response->successful()) {
                throw new Exception('Failed to fetch parliamentary JSON');
            }

            if (app()->isLocal()) {
                $dir = base_path("data/{$legislature->sigla}/initiatives");
                File::ensureDirectoryExists($dir);
            }

            foreach (Items::fromFile($path, ['decoder' => new ExtJsonDecoder(true)]) as $item) {
                if (app()->isLocal()) {
                    File::put($dir.'/'.$item['IniId'].'.json', json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                DB::transaction(function () use ($legislature, $item) {
                    $this->syncInitiative($legislature, $item);
                });
            }
        } finally {
            File::delete($path);
        }
    }

    private function syncInitiative(Legislature $legislature, array $item): void
    {
        $initiativeId = $item['IniId'];

        $entryDate = $this->extractEntryDate($item['IniEventos'] ?? []);
        $latestVote = $this->extractLatestVote($item['IniEventos'] ?? []);
        $author = $this->detectAuthor($item);

        $finalVoteDate = null;
        $daysToApproval = null;

        if ($latestVote) {
            $finalVoteDate = Carbon::parse($latestVote['data']);
            $daysToApproval = $entryDate?->diffInDays($finalVoteDate);
        }

        $status = $this->determineStatus($item['IniEventos'] ?? [], $latestVote);

        $initiative = Initiative::updateOrCreate(
            ['id' => $initiativeId],
            [
                'legislature_id' => $legislature->id,
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

    /**
     * The Parliament records an initiative that lapsed (at the end of the legislature, or of the
     * government for its proposals) or was withdrawn as a phase of its own. That outcome is final,
     * so it outranks the result of any earlier vote, such as an approval na generalidade.
     */
    private function determineStatus(array $events, ?array $latestVote): InitiativeStatus
    {
        $phases = collect($events)->pluck('CodigoFase');

        if ($phases->contains(self::PHASE_LAPSED)) {
            return InitiativeStatus::Lapsed;
        }

        if ($phases->contains(self::PHASE_WITHDRAWN)) {
            return InitiativeStatus::Withdrawn;
        }

        return match (strtolower($latestVote['resultado'] ?? '')) {
            'aprovado' => InitiativeStatus::Approved,
            'rejeitado' => InitiativeStatus::Rejected,
            default => InitiativeStatus::InProgress,
        };
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
