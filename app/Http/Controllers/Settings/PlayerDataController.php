<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SeedPlayerDataRequest;
use App\Services\PlayerDataSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PlayerDataController extends Controller
{
    /**
     * Replace the player list with the DBBL roster. Runs only once.
     */
    public function store(SeedPlayerDataRequest $request, PlayerDataSeeder $seeder): RedirectResponse
    {
        try {
            $seeded = $seeder->seed($request->user());
        } catch (UniqueConstraintViolationException) {
            // Another admin seeded between validation and now.
            throw ValidationException::withMessages([
                'seed' => __('Player data has already been seeded.'),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Seeded :players bladers across :teams teams.', $seeded),
        ]);

        return to_route('profile.edit');
    }
}
