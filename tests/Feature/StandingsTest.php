<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\Team;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Pull a single award result out of the computed standings.
 *
 * @return array{key: string, name: string, description: string, metric: string, winner: array{player_id: int, player_name: string, value: int}|null}
 */
function award(string $key): array
{
    $awards = collect(app(StandingsService::class)->compute());

    return $awards->firstWhere('key', $key);
}

test('the standings page is publicly accessible', function () {
    $this->get(route('standings.index'))->assertOk();
});

test('finals mvp is the player with the highest total score', function () {
    $low = Player::factory()->create(['blader_name' => 'Low']);
    $high = Player::factory()->create(['blader_name' => 'High']);

    PlayerScore::factory()->for($low)->score(1)->count(2)->create();  // total 2
    PlayerScore::factory()->for($high)->score(3)->count(2)->create(); // total 6

    expect(award('finals_mvp')['winner']['player_id'])->toBe($high->id)
        ->and(award('finals_mvp')['winner']['value'])->toBe(6);
});

test('rookie is the player with the most recent start date who has points', function () {
    $veteran = Player::factory()->startedOn('2024-01-01')->create();
    $rookie = Player::factory()->startedOn('2025-06-01')->create();

    PlayerScore::factory()->for($veteran)->score(3)->create();
    PlayerScore::factory()->for($rookie)->score(1)->create();

    expect(award('rookie_of_the_season')['winner']['player_id'])->toBe($rookie->id);
});

test('rookie is not awarded when no player has a start date', function () {
    $player = Player::factory()->create(['date_started' => null]);
    PlayerScore::factory()->for($player)->score(3)->create();

    expect(award('rookie_of_the_season')['winner'])->toBeNull();
});

test('stamina king has the most entries scoring 1', function () {
    $winner = Player::factory()->create();
    $other = Player::factory()->create();

    PlayerScore::factory()->for($winner)->score(1)->count(3)->create();
    PlayerScore::factory()->for($other)->score(1)->count(1)->create();

    expect(award('stamina_king')['winner']['player_id'])->toBe($winner->id)
        ->and(award('stamina_king')['winner']['value'])->toBe(3);
});

test('extreme champion has the most entries scoring 3', function () {
    $winner = Player::factory()->create();
    PlayerScore::factory()->for($winner)->score(3)->count(4)->create();

    expect(award('extreme_champion')['winner']['player_id'])->toBe($winner->id)
        ->and(award('extreme_champion')['winner']['value'])->toBe(4);
});

test('over lord counts over finishes only, not bursts', function () {
    $overs = Player::factory()->create();
    $bursts = Player::factory()->create();

    PlayerScore::factory()->for($overs)->score(2)->count(2)->create();
    // Bursts also score 2, but they belong to Burst God.
    PlayerScore::factory()->for($bursts)->burst()->count(5)->create();

    $overLord = collect(app(StandingsService::class)->leaderboards(3))
        ->firstWhere('key', 'over_lord');

    expect($overLord['leaders'])->toHaveCount(1)
        ->and($overLord['leaders'][0]['player_id'])->toBe($overs->id)
        ->and($overLord['leaders'][0]['value'])->toBe(2);
});

test('burst god has the most burst-finish entries', function () {
    $winner = Player::factory()->create();
    $other = Player::factory()->create();

    PlayerScore::factory()->for($winner)->burst()->count(3)->create();
    PlayerScore::factory()->for($other)->burst()->count(1)->create();
    // A plain score of 2 should not count towards burst god.
    PlayerScore::factory()->for($other)->score(2)->count(5)->create();

    expect(award('burst_god')['winner']['player_id'])->toBe($winner->id)
        ->and(award('burst_god')['winner']['value'])->toBe(3);
});

test('leaderboards return the top contenders in order, capped at the limit', function () {
    collect(['A' => 5, 'B' => 3, 'C' => 2, 'D' => 1])
        ->each(function (int $spins, string $name): void {
            $player = Player::factory()->create(['blader_name' => $name]);
            PlayerScore::factory()->for($player)->score(1)->count($spins)->create();
        });

    $stamina = collect(app(StandingsService::class)->leaderboards(3))
        ->firstWhere('key', 'stamina_king');

    expect($stamina['leaders'])->toHaveCount(3)
        ->and(array_column($stamina['leaders'], 'player_name'))->toBe(['A', 'B', 'C'])
        ->and(array_column($stamina['leaders'], 'value'))->toBe([5, 3, 2]);
});

test('the player leaderboard ranks by points with ties sharing a rank', function () {
    $alpha = Player::factory()->create(['blader_name' => 'Alpha']);
    $bravo = Player::factory()->create(['blader_name' => 'Bravo']);
    $charlie = Player::factory()->create(['blader_name' => 'Charlie']);
    Player::factory()->create(['blader_name' => 'Delta']); // no battles

    PlayerScore::factory()->for($alpha)->score(3)->count(2)->create(); // 6
    PlayerScore::factory()->for($bravo)->score(1)->count(3)->create(); // 3
    PlayerScore::factory()->for($charlie)->score(3)->create();        // 3

    $rows = collect(app(StandingsService::class)->playerLeaderboard());

    expect($rows->pluck('player_name')->all())->toBe(['Alpha', 'Bravo', 'Charlie', 'Delta'])
        ->and($rows->pluck('rank')->all())->toBe([1, 2, 2, 4])
        ->and($rows->pluck('points')->all())->toBe([6, 3, 3, 0]);
});

test('the player leaderboard breaks battles down by finish type', function () {
    $player = Player::factory()->create();

    PlayerScore::factory()->for($player)->score(1)->count(2)->create(); // spin
    PlayerScore::factory()->for($player)->score(2)->create();           // over
    PlayerScore::factory()->for($player)->burst()->count(3)->create();  // burst
    PlayerScore::factory()->for($player)->score(3)->create();           // extreme

    $row = app(StandingsService::class)->playerLeaderboard()[0];

    expect($row)->toMatchArray([
        'battles' => 7,
        'points' => 2 + 2 + 6 + 3,
        'spin' => 2,
        'over' => 1,
        'burst' => 3,
        'extreme' => 1,
    ]);
});

test('a player profile includes rank, totals, placements and recent battles', function () {
    $rookie = Player::factory()->startedOn('2025-06-01')->create(['blader_name' => 'Rookie']);
    $veteran = Player::factory()->startedOn('2024-01-01')->create(['blader_name' => 'Veteran']);

    PlayerScore::factory()->for($rookie)->score(3)->count(3)->create(); // 9
    PlayerScore::factory()->for($veteran)->score(1)->create();          // 1

    $profile = app(StandingsService::class)->playerProfile($rookie);

    expect($profile['rank'])->toBe(1)
        ->and($profile['total_players'])->toBe(2)
        ->and($profile['points'])->toBe(9)
        ->and($profile['battles'])->toBe(3)
        ->and($profile['average'])->toBe(3.0)
        ->and($profile['breakdown']['extreme'])->toBe(3)
        ->and($profile['recent'])->toHaveCount(3)
        ->and(collect($profile['placements'])->firstWhere('key', 'finals_mvp')['position'])->toBe(1)
        ->and(collect($profile['placements'])->firstWhere('key', 'rookie_of_the_season')['position'])->toBe(1);
});

test('a player without battles is not ranked', function () {
    $player = Player::factory()->create();

    $profile = app(StandingsService::class)->playerProfile($player);

    expect($profile['rank'])->toBeNull()
        ->and($profile['battles'])->toBe(0)
        ->and($profile['average'])->toBe(0.0)
        ->and($profile['placements'])->toBe([])
        ->and($profile['recent'])->toBe([]);
});

test('the standings page defers the leaderboard and has no player by default', function () {
    PlayerScore::factory()->score(2)->create();

    $this->get(route('standings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('standings/Index')
            ->missing('awards')
            ->missing('leaderboard')
            ->missing('stats')
            ->where('selectedPlayer', null)
            ->loadDeferredProps(fn ($reload) => $reload
                ->has('awards', 6)
                ->has('awards.0.leaders')
                ->has('leaderboard', 1)
                ->where('stats', ['players' => 1, 'scores' => 1])));
});

test('refreshing reloads only the standings data', function () {
    PlayerScore::factory()->score(3)->create();

    $this->get(route('standings.index'), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'standings/Index',
        'X-Inertia-Partial-Data' => 'awards,leaderboard,stats',
    ])
        ->assertOk()
        ->assertJsonCount(6, 'props.awards')
        ->assertJsonPath('props.leaderboard.0.points', 3)
        ->assertJsonPath('props.stats', ['players' => 1, 'scores' => 1])
        ->assertJsonMissingPath('props.selectedPlayer');
});

test('a deep link opens a player stat card for guests', function () {
    $player = Player::factory()->create(['blader_name' => 'Linked']);
    PlayerScore::factory()->for($player)->score(3)->create();

    $this->get(route('standings.index', ['player' => $player->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            // The stat card isn't deferred, so it opens without a second request.
            ->missing('leaderboard')
            ->where('selectedPlayer.player_name', 'Linked')
            ->where('selectedPlayer.points', 3)
            ->has('selectedPlayer.recent', 1));
});

test('clicking a player loads only their stat card via a partial reload', function () {
    $player = Player::factory()->create();
    PlayerScore::factory()->for($player)->score(1)->create();

    $this->get(route('standings.index', ['player' => $player->id]), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'standings/Index',
        'X-Inertia-Partial-Data' => 'selectedPlayer',
    ])
        ->assertOk()
        ->assertJsonPath('props.selectedPlayer.player_id', $player->id)
        ->assertJsonMissingPath('props.leaderboard')
        ->assertJsonMissingPath('props.awards');
});

test('standings show blader names and team logos, never real names', function () {
    $team = Team::factory()->create(['name' => 'Highland Bladers', 'acronym' => 'HBK', 'logo_path' => 'images/teams/hbk.webp']);
    $player = Player::factory()->onTeam($team)->create(['blader_name' => 'HAVOC', 'name' => 'Ralph Jan Santos']);
    PlayerScore::factory()->for($player)->score(3)->create();

    $expectedTeam = ['name' => 'Highland Bladers', 'acronym' => 'HBK', 'logo_url' => asset('images/teams/hbk.webp')];

    $this->get(route('standings.index', ['player' => $player->id]))
        ->assertOk()
        ->assertDontSee('Ralph Jan Santos')
        ->assertInertia(fn ($page) => $page
            ->where('selectedPlayer.player_name', 'HAVOC')
            ->where('selectedPlayer.team', $expectedTeam)
            ->loadDeferredProps(fn ($reload) => $reload
                ->where('leaderboard.0.player_name', 'HAVOC')
                ->where('leaderboard.0.team', $expectedTeam)
                ->where('awards.0.leaders.0.player_name', 'HAVOC')
                ->where('awards.0.leaders.0.team', $expectedTeam)));
});

test('players without a team have no team on the standings', function () {
    PlayerScore::factory()->score(1)->create();

    expect(app(StandingsService::class)->playerLeaderboard()[0]['team'])->toBeNull();
});

test('an unknown player id yields no stat card', function () {
    $this->get(route('standings.index', ['player' => 999_999]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('selectedPlayer', null));
});

test('awards without qualifying entries have no winner', function () {
    expect(award('finals_mvp')['winner'])->toBeNull()
        ->and(award('burst_god')['winner'])->toBeNull();
});
