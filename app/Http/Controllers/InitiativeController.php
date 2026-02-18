<?php

namespace App\Http\Controllers;

use App\Models\Initiative;
use App\Services\VotePositionParserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InitiativeController extends Controller
{
    public function index(Request $request): View|Response
    {
        $initiatives = Initiative::query()
            ->filterByStatus($request->query('status'))
            ->filterByAuthorCategory($request->query('author_category'))
            ->filterBySearch($request->query('search'))
            ->orderByDesc('entry_date')
            ->simplePaginate(20);

        if ($request->ajax()) {
            return response(
                view('pages.initiatives._cards', ['initiatives' => $initiatives])->render(),
                200,
                ['X-Has-More-Pages' => $initiatives->hasMorePages() ? 'true' : 'false'],
            );
        }

        return view('pages.initiatives.index', [
            'initiatives' => $initiatives,
        ]);
    }

    public function show(Initiative $initiative, VotePositionParserService $parser): View
    {
        $initiative->load('votes.positions');

        $votesWithPositions = $initiative->votes
            ->sortByDesc('date')
            ->map(function ($vote) use ($parser) {
                $parsed = $parser->parseAll($vote->positions);

                return [
                    'vote' => $vote,
                    'positions' => $parsed,
                    'counts' => [
                        'favor' => count($parsed['favor']),
                        'contra' => count($parsed['contra']),
                        'abstencao' => count($parsed['abstencao']),
                    ],
                ];
            });

        return view('pages.initiatives.show', [
            'initiative' => $initiative,
            'votesWithPositions' => $votesWithPositions,
        ]);
    }
}
