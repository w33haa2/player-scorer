<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\User;
use App\Services\StandingsService;
use App\Services\TournamentDays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.tournament_timezone' => 'Asia/Manila']);
});

/**
 * Record a score at a given moment (UTC, the storage timezone).
 */
function recordScoreAt(Player $player, int $score, string $utc): PlayerScore
{
    return PlayerScore::factory()->for($player)->score($score)->create([
        'created_at' => $utc,
        'updated_at' => $utc,
    ]);
}

/**
 * Two days of battles: "Early" only plays Day 1, "Late" plays both.
 *
 * @return array{early: Player, late: Player}
 */
function twoDayTournament(): array
{
    $early = Player::factory()->create(['blader_name' => 'Early']);
    $late = Player::factory()->create(['blader_name' => 'Late']);

    recordScoreAt($early, 3, '2026-10-03 03:00:00'); // Day 1, 11:00 in Manila
    recordScoreAt($early, 3, '2026-10-03 04:00:00'); // Day 1
    recordScoreAt($late, 1, '2026-10-03 05:00:00');  // Day 1
    recordScoreAt($late, 3, '2026-10-04 03:00:00');  // Day 2
    recordScoreAt($late, 3, '2026-10-04 04:00:00');  // Day 2

    return ['early' => $early, 'late' => $late];
}

test('tournament days follow the local calendar, not UTC', function () {
    $player = Player::factory()->create();

    recordScoreAt($player, 1, '2026-10-03 03:00:00'); // Oct 3, 11:00 in Manila
    recordScoreAt($player, 2, '2026-10-03 17:30:00'); // Oct 4, 01:30 in Manila
    recordScoreAt($player, 3, '2026-10-04 09:00:00'); // Oct 4, 17:00 in Manila

    $days = app(TournamentDays::class);

    expect($days->all())->toHaveCount(2)
        ->and($days->all()[0]->date->toDateString())->toBe('2026-10-03')
        ->and($days->all()[1]->date->toDateString())->toBe('2026-10-04')
        ->and($days->latest()->number)->toBe(2)
        ->and($days->dayOf(Carbon::parse('2026-10-03 17:30:00', 'UTC'))->number)->toBe(2);
});

test('there are no tournament days before any battle is recorded', function () {
    expect(app(TournamentDays::class)->all())->toBe([])
        ->and(app(TournamentDays::class)->latest())->toBeNull();

    $this->get(route('standings.index'))
        ->assertInertia(fn ($page) => $page->where('days', [])->where('day', null));
});

test('the standings can aggregate a single day', function () {
    twoDayTournament();

    $this->get(route('standings.index', ['day' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('day', 2)
            ->has('days', 2)
            ->where('days.0.label', 'Day 1')
            ->where('days.0.date', '2026-10-03')
            ->where('days.1.label', 'Day 2')
            ->loadDeferredProps(fn ($reload) => $reload
                ->where('leaderboard.0.player_name', 'Late')
                ->where('leaderboard.0.points', 6)
                // Players who didn't battle that day are still listed.
                ->where('leaderboard.1.player_name', 'Early')
                ->where('leaderboard.1.points', 0)
                ->where('leaderboard.1.battles', 0)
                ->where('awards.0.key', 'finals_mvp')
                ->has('awards.0.leaders', 1)
                ->where('awards.0.leaders.0.player_name', 'Late')
                ->where('stats.scores', 2)));
});

test('without a day the standings aggregate the whole tournament', function () {
    twoDayTournament();

    $this->get(route('standings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('day', null)
            ->loadDeferredProps(fn ($reload) => $reload
                ->where('leaderboard.0.player_name', 'Late')
                ->where('leaderboard.0.points', 7)
                ->where('leaderboard.1.points', 6)
                ->where('stats.scores', 5)));
});

test('an unknown day falls back to the whole tournament', function (mixed $day) {
    twoDayTournament();

    $this->get(route('standings.index', ['day' => $day]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('day', null)
            ->loadDeferredProps(fn ($reload) => $reload->where('stats.scores', 5)));
})->with([0, 3, -1, 'two']);

test('switching or refreshing a day reloads only that day', function () {
    twoDayTournament();

    $this->get(route('standings.index', ['day' => 1]), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'standings/Index',
        'X-Inertia-Partial-Data' => 'awards,leaderboard,stats,days,day',
    ])
        ->assertOk()
        ->assertJsonPath('props.day', 1)
        ->assertJsonPath('props.stats.scores', 3)
        ->assertJsonPath('props.leaderboard.0.player_name', 'Early')
        ->assertJsonPath('props.leaderboard.0.points', 6)
        ->assertJsonMissingPath('props.selectedPlayer');
});

test('the day being played is flagged as today', function () {
    twoDayTournament();

    // Oct 4, 14:00 in Manila: Day 2 is live.
    $this->travelTo(Carbon::parse('2026-10-04 06:00:00', 'UTC'));

    $this->get(route('standings.index'))
        ->assertInertia(fn ($page) => $page
            ->where('days.0.is_today', false)
            ->where('days.1.is_today', true));

    // The day after the event, nothing is live.
    $this->travelTo(Carbon::parse('2026-10-05 06:00:00', 'UTC'));

    $this->get(route('standings.index'))
        ->assertInertia(fn ($page) => $page->where('days.1.is_today', false));
});

test('a player card splits points and battles by day', function () {
    ['early' => $early, 'late' => $late] = twoDayTournament();

    $service = app(StandingsService::class);

    expect($service->playerProfile($late)['days'])->toBe([
        ['number' => 1, 'label' => 'Day 1', 'date' => '2026-10-03', 'points' => 1, 'battles' => 1],
        ['number' => 2, 'label' => 'Day 2', 'date' => '2026-10-04', 'points' => 6, 'battles' => 2],
    ])
        ->and(array_column($service->playerProfile($early)['days'], 'points'))->toBe([6, 0])
        // The card itself stays whole-tournament.
        ->and($service->playerProfile($late)['points'])->toBe(7);
});

test('the dashboard can aggregate a single day', function () {
    twoDayTournament();
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard', ['day' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('day', 1)
            ->has('days', 2)
            ->loadDeferredProps(fn ($reload) => $reload
                ->where('stats.battles', 3)
                ->where('stats.points', 7)
                ->where('leaderboards.0.leaders.0.player_name', 'Early')
                ->where('leaderboards.0.leaders.0.value', 6)
                // Recent activity isn't filtered by day.
                ->has('recentActivity', 5)));
});

test('a day view has its own title and stays out of search results', function () {
    config(['app.url' => 'https://dbbl.test', 'app.name' => 'DBBL Player Scorer']);
    twoDayTournament();

    $this->get(route('standings.index', ['day' => 1]))
        ->assertOk()
        ->assertSee('<title>Day 1 standings &amp; title race - DBBL Player Scorer</title>', false)
        ->assertSee('Early leads Finals MVP with 6 points on Day 1.', false)
        ->assertSee('3 battles recorded that day.', false)
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<meta property="og:url" content="https://dbbl.test/standings?day=1">', false);
});
