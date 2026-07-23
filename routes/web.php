<?php

use App\Http\Controllers\ActionLogController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\StandingsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Public: current award standings.
Route::get('standings', [StandingsController::class, 'index'])->name('standings.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

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
