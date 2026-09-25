<?php

use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.url' => 'https://dbbl.test', 'app.name' => 'DBBL Player Scorer']);
});

test('the landing page is indexable with its own title, description and social tags', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<title>Davao Beyblade Battle League scores &amp; standings - DBBL Player Scorer</title>', false)
        ->assertSee('<meta name="description" content="Live scores, standings and the title race', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="https://dbbl.test/">', false)
        ->assertSee('<meta property="og:url" content="https://dbbl.test/">', false)
        ->assertSee('<meta property="og:image" content="https://dbbl.test/og-image.png">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

test('the standings page describes the current leader from live data', function () {
    $player = Player::factory()->create(['name' => 'Valkyrie']);
    PlayerScore::factory()->for($player)->score(3)->count(2)->create();

    $this->get(route('standings.index'))
        ->assertOk()
        ->assertSee('<title>Standings &amp; title race - DBBL Player Scorer</title>', false)
        ->assertSee('Valkyrie leads Finals MVP with 6 points.', false)
        ->assertSee('1 player, 2 battles recorded.', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="https://dbbl.test/standings">', false);
});

test('a shared player link previews that player but stays out of search results', function () {
    $player = Player::factory()->create(['name' => 'Dranzer']);
    PlayerScore::factory()->for($player)->score(3)->create();

    $this->get(route('standings.index', ['player' => $player->id]))
        ->assertOk()
        ->assertSee('<title>Dranzer player stats - DBBL Player Scorer</title>', false)
        ->assertSee('Dranzer is ranked #1 of 1 with 3 points from 1 battle. Holds Finals MVP', false)
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<meta property="og:url" content="https://dbbl.test/standings?player='.$player->id.'">', false)
        ->assertDontSee('rel="canonical"', false);
});

test('auth and admin pages are kept out of search results', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertDontSee('rel="canonical"', false);

    $this->actingAs(User::factory()->create());

    foreach (['dashboard', 'players.index', 'scores.index', 'audit-logs.index'] as $route) {
        $this->get(route($route))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
});

test('robots.txt blocks admin paths and points to the sitemap', function () {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/plain');

    $response->assertSee('User-agent: *', false)
        ->assertSee('Disallow: /dashboard', false)
        ->assertSee('Disallow: /players', false)
        ->assertSee('Disallow: /settings', false)
        ->assertSee('Sitemap: https://dbbl.test/sitemap.xml', false)
        ->assertDontSee('Disallow: /standings', false);
});

test('the sitemap lists the public pages', function () {
    PlayerScore::factory()->create();

    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/xml');

    $response->assertSee('<loc>https://dbbl.test/</loc>', false)
        ->assertSee('<loc>https://dbbl.test/standings</loc>', false)
        ->assertSee('<lastmod>', false)
        ->assertDontSee('/dashboard', false);
});
