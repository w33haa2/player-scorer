<?php

use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('audit logs can be separated by the day the change was made', function () {
    config(['app.tournament_timezone' => 'Asia/Manila']);
    $this->actingAs(User::factory()->create());
    $player = Player::factory()->create();

    // Day 1: two scores recorded.
    $this->travelTo(Carbon::parse('2026-10-03 03:00:00', 'UTC'));
    $score = PlayerScore::factory()->for($player)->score(1)->create();
    PlayerScore::factory()->for($player)->score(3)->create();

    // Day 2: one new score, and a correction to a Day 1 score.
    $this->travelTo(Carbon::parse('2026-10-04 03:00:00', 'UTC'));
    PlayerScore::factory()->for($player)->score(2)->create();
    $score->update(['score' => 3, 'is_burst' => false]);

    $this->get(route('audit-logs.index', ['day' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.day', 1)
            ->has('days', 2)
            ->has('logs.data', 2)
            ->where('logs.data.0.day', 1));

    // The Day 1 score's correction happened on Day 2, so it's listed there.
    $this->get(route('audit-logs.index', ['day' => 2]))
        ->assertInertia(fn ($page) => $page
            ->has('logs.data', 2)
            ->where('logs.data.0.day', 2)
            ->where('logs.data.0.changes.action', 'updated')
            ->where('logs.data.0.changes.score_id', $score->id));

    $this->get(route('audit-logs.index'))
        ->assertInertia(fn ($page) => $page->where('filters.day', null)->has('logs.data', 4));
});

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
