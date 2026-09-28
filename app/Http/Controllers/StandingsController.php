<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\PlayerScore;
use App\Services\Seo;
use App\Services\StandingsService;
use App\Services\TournamentDay;
use App\Services\TournamentDays;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StandingsController extends Controller
{
    public const PAGE_TITLE = 'Standings & title race';

    /**
     * Display the public tournament leaderboard.
     *
     * `?player={id}` opens that player's stat card (shareable link). Props are
     * lazy closures, memoized with once(), so a partial reload for one player
     * doesn't compute the whole leaderboard.
     *
     * The standings data is deferred so the page paints with skeletons first.
     * `selectedPlayer` and `seo` stay in the initial response: deep links open
     * straight away and link previews get real metadata.
     *
     * `?day={n}` aggregates a single tournament day; anything else (or an
     * unknown day) shows the whole tournament. The player card is always
     * whole-tournament, with a per-day split.
     */
    public function index(Request $request, StandingsService $standings, TournamentDays $tournamentDays): Response
    {
        $playerId = $request->integer('player');
        $day = $tournamentDays->find($request->integer('day'));
        $scoped = $standings->forDay($day);

        $awards = fn (): array => once(fn () => $scoped->leaderboards(3));

        $stats = fn (): array => once(fn () => [
            'players' => Player::count(),
            'scores' => PlayerScore::query()->onDay($day)->count(),
        ]);

        $profile = fn (): ?array => once(function () use ($playerId, $standings): ?array {
            $player = $playerId > 0 ? Player::find($playerId) : null;

            return $player ? $standings->playerProfile($player) : null;
        });

        return Inertia::render('standings/Index', [
            'awards' => Inertia::defer($awards),
            'leaderboard' => Inertia::defer(fn (): array => $scoped->playerLeaderboard()),
            'stats' => Inertia::defer($stats),
            'days' => fn (): array => array_map(fn (TournamentDay $tournamentDay): array => $tournamentDay->toArray(), $tournamentDays->all()),
            'day' => $day?->number,
            'selectedPlayer' => $profile,
            'seo' => fn (): array => $this->seo($awards(), $stats(), $profile(), $day),
        ]);
    }

    /**
     * Page title for the whole tournament or a single day.
     */
    public static function title(?TournamentDay $day): string
    {
        return $day ? "{$day->label()} standings & title race" : self::PAGE_TITLE;
    }

    /**
     * Build page metadata from live standings so shared links preview well.
     *
     * @param  list<array{key: string, name: string, leaders: list<array{player_name: string, value: int}>}>  $awards
     * @param  array{players: int, scores: int}  $stats
     * @param  array{player_id: int, player_name: string, rank: int|null, total_players: int, battles: int, points: int, placements: list<array{name: string, position: int}>}|null  $profile
     * @return array<string, string|null>
     */
    private function seo(array $awards, array $stats, ?array $profile, ?TournamentDay $day): array
    {
        if ($profile !== null) {
            return Seo::page(
                title: "{$profile['player_name']} player stats",
                description: $this->playerDescription($profile),
                path: 'standings?player='.$profile['player_id'],
                // Player cards stay out of search results; link previews still work.
                index: false,
            );
        }

        $leader = collect($awards)->firstWhere('key', 'finals_mvp')['leaders'][0] ?? null;

        $description = $leader
            ? "{$leader['player_name']} leads Finals MVP with {$leader['value']} points".($day ? " on {$day->label()}" : '').'. '
            : '';

        if ($day !== null) {
            $description .= "DBBL {$day->label()} leaderboard and title race: {$stats['scores']} "
                .str('battle')->plural($stats['scores']).' recorded that day.';

            // Day views are filtered copies of /standings; keep them out of search.
            return Seo::page(
                title: self::title($day),
                description: $description,
                path: 'standings?day='.$day->number,
                index: false,
            );
        }

        $description .= "Live DBBL leaderboard and title race: {$stats['players']} "
            .str('player')->plural($stats['players']).", {$stats['scores']} "
            .str('battle')->plural($stats['scores']).' recorded.';

        return Seo::page(
            title: self::PAGE_TITLE,
            description: $description,
            path: 'standings',
        );
    }

    /**
     * @param  array{player_name: string, rank: int|null, total_players: int, battles: int, points: int, placements: list<array{name: string, position: int}>}  $profile
     */
    private function playerDescription(array $profile): string
    {
        if ($profile['rank'] === null) {
            return "{$profile['player_name']} hasn't recorded a battle in the DBBL round robin yet.";
        }

        $description = "{$profile['player_name']} is ranked #{$profile['rank']} of {$profile['total_players']} "
            ."with {$profile['points']} points from {$profile['battles']} "
            .str('battle')->plural($profile['battles']).'.';

        $held = collect($profile['placements'])
            ->where('position', 1)
            ->pluck('name');

        if ($held->isNotEmpty()) {
            $description .= ' Holds '.$held->join(', ', ' and ').'.';
        }

        return $description;
    }
}
