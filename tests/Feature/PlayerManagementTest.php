<?php

use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests cannot view the players page', function () {
    $this->get(route('players.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the players page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('players.index'))->assertOk();
});

test('a player can be created', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('players.store'), [
        'name' => 'Ryu',
        'date_started' => '2025-01-01',
    ])->assertRedirect(route('players.index'));

    $player = Player::firstWhere('name', 'Ryu');

    expect($player)->not->toBeNull()
        ->and($player->date_started->toDateString())->toBe('2025-01-01');
});

test('a player name is required', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('players.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('a player can be updated', function () {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create(['name' => 'Ken']);

    $this->from(route('players.index'))
        ->put(route('players.update', $player), [
            'name' => 'Ken Masters',
            'date_started' => null,
        ])
        ->assertRedirect(route('players.index'));

    expect($player->fresh()->name)->toBe('Ken Masters');
});

test('players can be searched by name', function () {
    $this->actingAs(User::factory()->create());
    Player::factory()->create(['name' => 'Valkyrie']);
    Player::factory()->create(['name' => 'Dragoon']);

    $response = $this->get(route('players.index', ['search' => 'valk']));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('players/Index')
        ->has('players.data', 1)
        ->where('players.data.0.name', 'Valkyrie')
    );
});

test('a player can be deleted along with their scores', function () {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create();
    PlayerScore::factory()->for($player)->create();

    $this->from(route('players.index'))
        ->delete(route('players.destroy', $player))
        ->assertRedirect(route('players.index'));

    $this->assertDatabaseMissing('players', ['id' => $player->id]);
    $this->assertDatabaseMissing('player_scores', ['player_id' => $player->id]);
});
