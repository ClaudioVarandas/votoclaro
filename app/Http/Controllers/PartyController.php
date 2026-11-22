<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowPartyRequest;
use App\Models\Initiative;
use App\Models\VotePosition;
use App\Services\LegislatureContext;
use App\Services\PartyStatsService;
use Illuminate\Contracts\View\View;

class PartyController extends Controller
{
    public function __construct(private LegislatureContext $legislatureContext) {}

    public function index(PartyStatsService $partyStats): View
    {
        return view('pages.parties.index', [
            'partyStats' => $partyStats->getAllPartyStats($this->legislatureContext->activeOrFail()),
        ]);
    }

    public function show(ShowPartyRequest $request, PartyStatsService $partyStats): View
    {
        $legislature = $this->legislatureContext->activeOrFail();
        $acronym = strtoupper($request->route('party'));
        $stats = $partyStats->getPartyStats($legislature, $acronym);

        if ($stats === null) {
            abort(404);
        }

        $sort = $request->validated('sort', 'date');
        $direction = $request->validated('direction', 'desc');
        $position = $request->validated('position');
        $type = $request->validated('type');
        $search = $request->validated('search');

        $baseQuery = VotePosition::with(['vote.initiative'])
            ->where('party', $acronym)
            ->whereHas('vote', fn ($query) => $query
                ->where('is_latest', true)
                ->whereRelation('initiative', 'legislature_id', $legislature->id));

        if ($type) {
            $baseQuery->whereHas('vote.initiative', fn ($q) => $q->where('initiative_type', $type));
        }

        if ($search) {
            $baseQuery->whereHas('vote.initiative', fn ($q) => $q->where('title', 'like', '%'.$search.'%'));
        }

        $positionCounts = [
            'total' => (clone $baseQuery)->count(),
            'favor' => (clone $baseQuery)->where('position', 'favor')->count(),
            'contra' => (clone $baseQuery)->where('position', 'contra')->count(),
            'abstencao' => (clone $baseQuery)->where('position', 'abstencao')->count(),
        ];

        if ($position) {
            $baseQuery->where('position', $position);
        }

        $sortColumn = match ($sort) {
            'result' => 'votes.result',
            default => 'initiatives.final_vote_date',
        };

        $votes = $baseQuery
            ->join('votes', 'vote_positions.vote_id', '=', 'votes.id')
            ->join('initiatives', 'votes.initiative_id', '=', 'initiatives.id')
            ->orderBy($sortColumn, $direction)
            ->select('vote_positions.*')
            ->paginate(20)
            ->withQueryString();

        $initiativeTypes = Initiative::query()
            ->forLegislature($legislature)
            ->whereNotNull('initiative_type')
            ->selectRaw('initiative_type, initiative_type_label')
            ->groupBy('initiative_type', 'initiative_type_label')
            ->orderBy('initiative_type_label')
            ->pluck('initiative_type_label', 'initiative_type');

        $authoredStatus = $request->validated('authored_status');
        $authoredType = $request->validated('authored_type');
        $authoredSearch = $request->validated('authored_search');
        $authoredSort = $request->validated('authored_sort', 'date');
        $authoredDirection = $request->validated('authored_direction', 'desc');

        $authoredBaseQuery = Initiative::query()->forLegislature($legislature)->where('author_party', $acronym);

        if ($authoredType) {
            $authoredBaseQuery->where('initiative_type', $authoredType);
        }

        if ($authoredSearch) {
            $authoredBaseQuery->where('title', 'like', '%'.$authoredSearch.'%');
        }

        $authoredStatusCounts = [
            'total' => (clone $authoredBaseQuery)->count(),
            'approved' => (clone $authoredBaseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $authoredBaseQuery)->where('status', 'rejected')->count(),
            'in_progress' => (clone $authoredBaseQuery)->where('status', 'in_progress')->count(),
        ];

        if ($authoredStatus) {
            $authoredBaseQuery->where('status', $authoredStatus);
        }

        $authoredSortColumn = match ($authoredSort) {
            'status' => 'status',
            default => 'entry_date',
        };

        $authoredInitiatives = $authoredBaseQuery
            ->orderBy($authoredSortColumn, $authoredDirection)
            ->paginate(20, ['*'], 'authored_page')
            ->withQueryString();

        $authoredTypes = Initiative::query()
            ->forLegislature($legislature)
            ->where('author_party', $acronym)
            ->whereNotNull('initiative_type')
            ->selectRaw('initiative_type, initiative_type_label')
            ->groupBy('initiative_type', 'initiative_type_label')
            ->orderBy('initiative_type_label')
            ->pluck('initiative_type_label', 'initiative_type');

        return view('pages.parties.show', [
            'stats' => $stats,
            'trend' => $partyStats->getMonthlyTrend($legislature, $acronym),
            'votes' => $votes,
            'positionCounts' => $positionCounts,
            'currentPosition' => $position,
            'currentType' => $type,
            'currentSearch' => $search,
            'currentSort' => $sort,
            'currentDirection' => $direction,
            'initiativeTypes' => $initiativeTypes,
            'authoredInitiatives' => $authoredInitiatives,
            'authoredStatusCounts' => $authoredStatusCounts,
            'authoredCurrentStatus' => $authoredStatus,
            'authoredCurrentType' => $authoredType,
            'authoredCurrentSearch' => $authoredSearch,
            'authoredCurrentSort' => $authoredSort,
            'authoredCurrentDirection' => $authoredDirection,
            'authoredTypes' => $authoredTypes,
        ]);
    }
}
