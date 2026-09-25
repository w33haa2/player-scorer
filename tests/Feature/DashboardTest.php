<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard defers its data so it can render skeletons first', function () {
    $this->actingAs(User::factory()->create());

    $leader = Player::factory()->create(['name' => 'Leader']);
    $runnerUp = Player::factory()->create(['name' => 'Runner Up']);

    PlayerScore::factory()->for($leader)->score(3)->count(2)->create();   // 6 pts
    PlayerScore::factory()->for($runnerUp)->score(1)->count(1)->create(); // 1 pt

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            // Not in the initial response: the page shows skeletons instead.
            ->missing('stats')
            ->missing('leaderboards')
            ->missing('recentActivity')
            ->loadDeferredProps(fn ($reload) => $reload
                ->has('leaderboards', 6)
                ->where('leaderboards.0.key', 'finals_mvp')
                ->where('leaderboards.0.leaders.0.player_name', 'Leader')
                ->where('leaderboards.0.leaders.0.value', 6)
                ->where('leaderboards.0.leaders.1.player_name', 'Runner Up')
                ->where('stats.players', 2)
                ->where('stats.battles', 3)
                ->where('stats.points', 7)
                ->has('recentActivity', 3)));
});

test('the dashboard refresh re-requests only the deferred sections', function () {
    $this->actingAs(User::factory()->create());
    PlayerScore::factory()->score(2)->create();

    // Mirrors router.reload({ only: [...] }) from the Refresh button.
    $this->get(route('dashboard'), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'Dashboard',
        'X-Inertia-Partial-Data' => 'stats,leaderboards,recentActivity',
    ])
        ->assertOk()
        ->assertJsonPath('props.stats.battles', 1)
        ->assertJsonCount(6, 'props.leaderboards')
        ->assertJsonCount(1, 'props.recentActivity');
});
