<?php

use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests cannot view the audit logs page', function () {
    $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
});

test('audit logs can be filtered by action', function () {
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create();

    // Creates one "created" log.
    $score = PlayerScore::factory()->for($player)->score(1)->create();
    // Creates one "updated" log.
    $score->update(['score' => 3, 'is_burst' => false]);

    $this->get(route('audit-logs.index', ['action' => 'updated']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('audit-logs/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.changes.action', 'updated'));
});
