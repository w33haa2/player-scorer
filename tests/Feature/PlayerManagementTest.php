<?php

use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
        'blader_name' => 'Pegasus',
        'name' => 'Ryu',
        'date_started' => '2025-01-01',
    ])->assertRedirect(route('players.index'));

    $player = Player::firstWhere('blader_name', 'Pegasus');

    expect($player)->not->toBeNull()
        ->and($player->name)->toBe('Ryu')
        ->and($player->date_started->toDateString())->toBe('2025-01-01');
});

test('a player can be created without a real name', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('players.store'), ['blader_name' => 'Pegasus', 'name' => null])
        ->assertSessionHasNoErrors();

    expect(Player::firstWhere('blader_name', 'Pegasus')->name)->toBeNull();
});

test('a blader name is required', function () {
    $this->actingAs(User::factory()->create());

    $this->post(route('players.store'), ['blader_name' => '', 'name' => 'Ryu'])
        ->assertSessionHasErrors('blader_name');
});

test('a player can be updated', function () {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create(['blader_name' => 'Ken', 'name' => null]);

    $this->from(route('players.index'))
        ->put(route('players.update', $player), [
            'blader_name' => 'Ken Masters',
            'name' => 'Ken',
            'date_started' => null,
        ])
        ->assertRedirect(route('players.index'));

    expect($player->fresh())
        ->blader_name->toBe('Ken Masters')
        ->name->toBe('Ken');
});

test('the players list includes each player\'s blader name and team', function () {
    $this->actingAs(User::factory()->create());
    $team = Team::factory()->create(['name' => 'Highland Bladers', 'acronym' => 'HBK', 'logo_path' => 'images/teams/hbk.webp']);
    Player::factory()->onTeam($team)->create(['blader_name' => 'HAVOC', 'name' => 'Ralph']);

    $this->get(route('players.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('players.data.0.blader_name', 'HAVOC')
            ->where('players.data.0.name', 'Ralph')
            ->where('players.data.0.team.name', 'Highland Bladers')
            ->where('players.data.0.team.acronym', 'HBK')
            ->where('players.data.0.team.logo_url', asset('images/teams/hbk.webp'))
            ->where('filters.sort', 'blader_name'));
});

test('players can be searched by blader name or real name', function (string $term) {
    $this->actingAs(User::factory()->create());
    Player::factory()->create(['blader_name' => 'Valkyrie', 'name' => 'Valt Aoi']);
    Player::factory()->create(['blader_name' => 'Dragoon', 'name' => 'Tyson']);

    $this->get(route('players.index', ['search' => $term]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('players/Index')
            ->has('players.data', 1)
            ->where('players.data.0.blader_name', 'Valkyrie'));
})->with(['valk', 'aoi']);

test('player search ranks results by relevance', function () {
    $this->actingAs(User::factory()->create());
    Player::factory()->create(['blader_name' => 'Marvalo', 'name' => null]);   // contains
    Player::factory()->create(['blader_name' => 'Valkyrie', 'name' => null]);  // starts with
    Player::factory()->create(['blader_name' => 'Val', 'name' => null]);       // exact

    $this->get(route('players.index', ['search' => 'val']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('players.data', 3)
            ->where('players.data.0.blader_name', 'Val')
            ->where('players.data.1.blader_name', 'Valkyrie')
            ->where('players.data.2.blader_name', 'Marvalo'));
});

test('player search is case insensitive', function (string $term) {
    $this->actingAs(User::factory()->create());
    Player::factory()->create(['blader_name' => 'Valkyrie', 'name' => null]);

    $this->get(route('players.index', ['search' => $term]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('players.data', 1)
            ->where('players.data.0.blader_name', 'Valkyrie'));
})->with(['VALKYRIE', 'valkyrie', 'VaLkYrIe', 'KYRIE']);

test('a player can be deleted along with their scores', function () {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->onTeam()->create();
    PlayerScore::factory()->for($player)->create();

    $this->from(route('players.index'))
        ->delete(route('players.destroy', $player))
        ->assertRedirect(route('players.index'));

    $this->assertDatabaseMissing('players', ['id' => $player->id]);
    $this->assertDatabaseMissing('player_scores', ['player_id' => $player->id]);
    $this->assertDatabaseMissing('team_members', ['player_id' => $player->id]);
});
