<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\PlayerScore;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StandingsController extends Controller
{
    /**
     * Display the public tournament leaderboard.
     *
     * `?player={id}` opens that player's stat card. It's resolved lazily, so
     * it's only computed on a deep-link visit or when the page requests it via
     * a partial reload (clicking a player).
     */
    public function index(Request $request, StandingsService $standings): Response
    {
        $playerId = $request->integer('player');

        return Inertia::render('standings/Index', [
            'awards' => $standings->leaderboards(3),
            'leaderboard' => $standings->playerLeaderboard(),
            'stats' => [
                'players' => Player::count(),
                'scores' => PlayerScore::count(),
            ],
            'selectedPlayer' => function () use ($playerId, $standings): ?array {
                $player = $playerId > 0 ? Player::find($playerId) : null;

                return $player ? $standings->playerProfile($player) : null;
            },
        ]);
    }
}
