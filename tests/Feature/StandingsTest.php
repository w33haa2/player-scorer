<?php

use App\Models\Player;
use App\Models\PlayerScore;
use App\Services\StandingsService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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

test('awards without qualifying entries have no winner', function () {
    expect(award('finals_mvp')['winner'])->toBeNull()
        ->and(award('burst_god')['winner'])->toBeNull();
});
