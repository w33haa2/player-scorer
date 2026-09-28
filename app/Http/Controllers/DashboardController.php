<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Player;
use App\Models\PlayerScore;
use App\Services\StandingsService;
use App\Services\TournamentDay;
use App\Services\TournamentDays;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin overview of the current award standings.
     *
     * The data sections are deferred so the page renders instantly with
     * skeleton placeholders, then fills in via a follow-up request. The same
     * keys are re-requested by the page's "Refresh" button.
     *
     * `?day={n}` limits the totals and title race to one tournament day.
     * Recent activity always shows the latest changes.
     */
    public function __invoke(Request $request, StandingsService $standings, TournamentDays $tournamentDays): Response
    {
        $day = $tournamentDays->find($request->integer('day'));

        return Inertia::render('Dashboard', [
            'stats' => Inertia::defer(fn (): array => [
                'players' => Player::count(),
                'battles' => PlayerScore::query()->onDay($day)->count(),
                'points' => (int) PlayerScore::query()->onDay($day)->sum('score'),
            ]),
            'leaderboards' => Inertia::defer(fn (): array => $standings->forDay($day)->leaderboards(3)),
            'recentActivity' => Inertia::defer(fn (): array => $this->recentActivity()),
            'days' => fn (): array => array_map(fn (TournamentDay $tournamentDay): array => $tournamentDay->toArray(), $tournamentDays->all()),
            'day' => $day?->number,
        ]);
    }

    /**
     * The latest score changes, with each player's current team for its logo.
     *
     * @return list<array{id: int, user_name: string, changes: array<string, mixed>, team: array{name: string, acronym: string|null, logo_url: string|null}|null, created_at: string|null}>
     */
    private function recentActivity(): array
    {
        $logs = ActionLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit(6)
            ->get();

        $players = Player::query()
            ->with('teams')
            ->whereIn('id', $logs->pluck('changes.player_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return array_values($logs->map(fn (ActionLog $log): array => [
            'id' => $log->id,
            'user_name' => $log->user->name ?? 'System',
            'changes' => $log->changes,
            'team' => $players->get($log->changes['player_id'] ?? 0)?->currentTeam()?->summary(),
            'created_at' => $log->created_at?->toIso8601String(),
        ])->all());
    }
}
