<?php

use App\Models\ActionLog;
use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('scores can be separated by tournament day', function () {
    config(['app.tournament_timezone' => 'Asia/Manila']);
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create();

    // Oct 3 and Oct 4 in Manila; 17:30 UTC on Oct 3 is already Oct 4 there.
    $this->travelTo(Carbon::parse('2026-10-03 03:00:00', 'UTC'));
    PlayerScore::factory()->for($player)->count(2)->create();
    $this->travelTo(Carbon::parse('2026-10-03 17:30:00', 'UTC'));
    PlayerScore::factory()->for($player)->count(3)->create();

    $this->get(route('scores.index', ['day' => 2]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scores/Index')
            ->where('filters.day', 2)
            ->has('days', 2)
            ->where('days.1.label', 'Day 2')
            ->has('scores.data', 3)
            ->where('scores.data.0.day', 2));

    $this->get(route('scores.index'))
        ->assertInertia(fn ($page) => $page
            ->where('filters.day', null)
            ->has('scores.data', 5)
            ->where('scores.data.4.day', 1));

    // Unknown days show every score.
    $this->get(route('scores.index', ['day' => 7]))
        ->assertInertia(fn ($page) => $page->where('filters.day', null)->has('scores.data', 5));
});

test('guests cannot store scores', function () {
    $player = Player::factory()->create();

    $this->post(route('players.scores.store', $player), [
        'scores' => [['score' => 1, 'is_burst' => false]],
    ])->assertRedirect(route('login'));
});

test('multiple scores can be stored for a player', function () {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create();

    $this->from(route('players.index'))
        ->post(route('players.scores.store', $player), [
            'scores' => [
                ['score' => 1, 'is_burst' => false],
                ['score' => 3, 'is_burst' => false],
                ['score' => 2, 'is_burst' => true],
            ],
        ])
        ->assertRedirect(route('players.index'));

    expect($player->scores()->count())->toBe(3);
});

test('a burst finish must have a score of exactly 2', function () {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create();

    $this->post(route('players.scores.store', $player), [
        'scores' => [['score' => 3, 'is_burst' => true]],
    ])->assertSessionHasErrors('scores.0.score');

    expect(PlayerScore::count())->toBe(0);
});

test('a score must be between 1 and 3', function (int $score) {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create();

    $this->post(route('players.scores.store', $player), [
        'scores' => [['score' => $score, 'is_burst' => false]],
    ])->assertSessionHasErrors('scores.0.score');
})->with([0, 4, 5]);

test('creating a score writes an action log entry', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $player = Player::factory()->create();

    $this->post(route('players.scores.store', $player), [
        'scores' => [['score' => 2, 'is_burst' => true]],
    ]);

    $log = ActionLog::first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->changes['action'])->toBe('created')
        ->and($log->changes['new']['score'])->toBe(2)
        ->and($log->changes['new']['is_burst'])->toBeTrue();
});

test('scores can be filtered by score value and burst finish', function () {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create();
    PlayerScore::factory()->for($player)->score(1)->create();
    PlayerScore::factory()->for($player)->score(3)->create();
    PlayerScore::factory()->for($player)->burst()->create();

    $this->get(route('scores.index', ['score' => 3]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('scores.data', 1)
            ->where('scores.data.0.score', 3));

    $this->get(route('scores.index', ['is_burst' => '1']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('scores.data', 1)
            ->where('scores.data.0.is_burst', true));
});

test('a score can be updated', function () {
    $this->actingAs(User::factory()->create());
    $score = PlayerScore::factory()->score(1)->create();

    $this->put(route('scores.update', $score), [
        'score' => 3,
        'is_burst' => false,
    ])->assertRedirect();

    expect($score->fresh()->score)->toBe(3);
});

test('updating a score writes an action log entry', function () {
    $this->actingAs(User::factory()->create());
    $score = PlayerScore::factory()->score(1)->create();

    $this->put(route('scores.update', $score), [
        'score' => 3,
        'is_burst' => false,
    ]);

    $log = ActionLog::where('changes->action', 'updated')->first();

    expect($log)->not->toBeNull()
        ->and($log->changes['old']['score'])->toBe(1)
        ->and($log->changes['new']['score'])->toBe(3);
});

test('updating a score to an invalid burst combination fails', function () {
    $this->actingAs(User::factory()->create());
    $score = PlayerScore::factory()->score(1)->create();

    $this->put(route('scores.update', $score), [
        'score' => 3,
        'is_burst' => true,
    ])->assertSessionHasErrors('score');
});
