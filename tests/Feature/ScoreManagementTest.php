<?php

use App\Models\ActionLog;
use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

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
