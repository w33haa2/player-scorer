<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Player;
use App\Models\PlayerScore;
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
    $low = Player::factory()->create(['name' => 'Low']);
    $high = Player::factory()->create(['name' => 'High']);

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
            $player = Player::factory()->create(['name' => $name]);
            PlayerScore::factory()->for($player)->score(1)->count($spins)->create();
        });

    $stamina = collect(app(StandingsService::class)->leaderboards(3))
        ->firstWhere('key', 'stamina_king');

    expect($stamina['leaders'])->toHaveCount(3)
        ->and(array_column($stamina['leaders'], 'player_name'))->toBe(['A', 'B', 'C'])
        ->and(array_column($stamina['leaders'], 'value'))->toBe([5, 3, 2]);
});

test('the player leaderboard ranks by points with ties sharing a rank', function () {
    $alpha = Player::factory()->create(['name' => 'Alpha']);
    $bravo = Player::factory()->create(['name' => 'Bravo']);
    $charlie = Player::factory()->create(['name' => 'Charlie']);
    Player::factory()->create(['name' => 'Delta']); // no battles

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
    $rookie = Player::factory()->startedOn('2025-06-01')->create(['name' => 'Rookie']);
    $veteran = Player::factory()->startedOn('2024-01-01')->create(['name' => 'Veteran']);

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

test('the standings page includes the leaderboard and no player by default', function () {
    PlayerScore::factory()->score(2)->create();

    $this->get(route('standings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('standings/Index')
            ->has('awards', 6)
            ->has('awards.0.leaders')
            ->has('leaderboard', 1)
            ->where('selectedPlayer', null));
});

test('a deep link opens a player stat card for guests', function () {
    $player = Player::factory()->create(['name' => 'Linked']);
    PlayerScore::factory()->for($player)->score(3)->create();

    $this->get(route('standings.index', ['player' => $player->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
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

test('an unknown player id yields no stat card', function () {
    $this->get(route('standings.index', ['player' => 999_999]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('selectedPlayer', null));
});

test('awards without qualifying entries have no winner', function () {
    expect(award('finals_mvp')['winner'])->toBeNull()
        ->and(award('burst_god')['winner'])->toBeNull();
});
