<?php

use App\Http\Controllers\ActionLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StandingsController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

// Crawler files, generated so they always use the current APP_URL.
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Public: current award standings.
Route::get('standings', [StandingsController::class, 'index'])->name('standings.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Players CRUD.
    Route::get('players', [PlayerController::class, 'index'])->name('players.index');
    Route::post('players', [PlayerController::class, 'store'])->name('players.store');
    Route::put('players/{player}', [PlayerController::class, 'update'])->name('players.update');
    Route::delete('players/{player}', [PlayerController::class, 'destroy'])->name('players.destroy');

    // Scores (create for a player, read list, update). No delete.
    Route::get('scores', [ScoreController::class, 'index'])->name('scores.index');
    Route::post('players/{player}/scores', [ScoreController::class, 'store'])->name('players.scores.store');
    Route::put('scores/{score}', [ScoreController::class, 'update'])->name('scores.update');

    // Audit logs.
    Route::get('audit-logs', [ActionLogController::class, 'index'])->name('audit-logs.index');
});

require __DIR__.'/settings.php';
