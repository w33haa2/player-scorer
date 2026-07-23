<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\PlayerScore;
use App\Services\StandingsService;
use Inertia\Inertia;
use Inertia\Response;

class StandingsController extends Controller
{
    /**
     * Display the public award standings.
     */
    public function index(StandingsService $standings): Response
    {
        return Inertia::render('standings/Index', [
            'awards' => $standings->compute(),
            'stats' => [
                'players' => Player::count(),
                'scores' => PlayerScore::count(),
            ],
        ]);
    }
}
