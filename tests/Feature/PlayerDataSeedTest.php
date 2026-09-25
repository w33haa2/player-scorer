<?php

use App\Models\ActionLog;
use App\Models\DataSeed;
use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\Team;
use App\Models\User;
use App\Services\PlayerDataSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot seed player data', function () {
    $this->post(route('player-data.store'), ['password' => 'password'])
        ->assertRedirect(route('login'));

    expect(Team::count())->toBe(0);
});

test('the profile page describes the roster before it is seeded', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->where('playerData', [
                'teams' => 24,
                'players' => 146,
                'seeded_at' => null,
                'seeded_by' => null,
            ]));
});

test('seeding replaces the sample players with the roster', function () {
    $user = User::factory()->create(['name' => 'Admin One']);
    $sample = Player::factory()->create(['blader_name' => 'Jiyo']);
    PlayerScore::factory()->for($sample)->count(3)->create(); // also writes audit logs

    expect(ActionLog::count())->toBe(3);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('player-data.store'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect(Player::count())->toBe(146)
        ->and(Team::count())->toBe(24)
        ->and(Player::find($sample->id))->toBeNull()
        ->and(PlayerScore::count())->toBe(0)
        ->and(ActionLog::count())->toBe(0)
        ->and(DataSeed::firstWhere('name', PlayerDataSeeder::NAME)->user_id)->toBe($user->id);

    $havoc = Player::firstWhere('blader_name', 'HAVOC');

    expect($havoc->name)->toBe('Ralph Jan Santos')
        ->and($havoc->currentTeam()->name)->toBe('Highlands - Bladers Kidapawan')
        ->and($havoc->currentTeam()->acronym)->toBe('HBK')
        ->and($havoc->currentTeam()->logo_path)->toBe('images/teams/highlands-bladers-kidapawan.webp')
        ->and(Player::firstWhere('blader_name', 'KNW')->name)->toBeNull()
        ->and(Team::firstWhere('name', 'TEAM EMINENCE')->acronym)->toBeNull()
        ->and(Team::firstWhere('name', 'SPNX Jr.')->players()->count())->toBe(7);
});

test('player data can only be seeded once', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('player-data.store'), ['password' => 'password']);

    // Players added after the seed must survive a second attempt.
    Player::factory()->create(['blader_name' => 'Late Entry']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('player-data.store'), ['password' => 'password'])
        ->assertSessionHasErrors('seed')
        ->assertRedirect(route('profile.edit'));

    expect(Player::count())->toBe(147)
        ->and(Player::where('blader_name', 'Late Entry')->exists())->toBeTrue()
        ->and(DataSeed::count())->toBe(1);
});

test('a second seed that slips past validation rolls back untouched', function () {
    $seeder = app(PlayerDataSeeder::class);
    $user = User::factory()->create();

    $seeder->seed($user);
    Player::factory()->create(['blader_name' => 'Late Entry']);

    expect(fn () => $seeder->seed($user))->toThrow(UniqueConstraintViolationException::class);

    expect(Player::count())->toBe(147)
        ->and(Team::count())->toBe(24);
});

test('the correct password is required to seed player data', function () {
    $sample = Player::factory()->create();

    $this->actingAs(User::factory()->create())
        ->from(route('profile.edit'))
        ->post(route('player-data.store'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect(Player::find($sample->id))->not->toBeNull()
        ->and(Team::count())->toBe(0)
        ->and(DataSeed::count())->toBe(0);
});

test('the profile page shows who seeded the player data', function () {
    $user = User::factory()->create(['name' => 'Admin Two']);

    app(PlayerDataSeeder::class)->seed($user);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('playerData.seeded_by', 'Admin Two')
            ->whereType('playerData.seeded_at', 'string'));
});

test('the roster file is complete and consistent', function () {
    $roster = app(PlayerDataSeeder::class)->roster();

    expect($roster)->toHaveCount(24)
        ->and(collect($roster)->pluck('name')->duplicates())->toBeEmpty();

    foreach ($roster as $team) {
        expect($team['players'])->not->toBeEmpty()
            ->and(public_path($team['logo']))->toBeFile();

        foreach ($team['players'] as $player) {
            expect(trim($player['blader_name']))->not->toBe('');

            // The jersey tag lives on the team, not in the blader name.
            if ($team['acronym'] !== null) {
                expect(str_starts_with(strtolower($player['blader_name']), strtolower($team['acronym'].'-')))->toBeFalse();
            }
        }
    }
});
