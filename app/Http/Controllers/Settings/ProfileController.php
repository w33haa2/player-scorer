<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\PlayerDataSeeder;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request, PlayerDataSeeder $seeder): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'playerData' => fn (): array => $this->playerData($seeder),
        ]);
    }

    /**
     * What the one-time roster seed will load, and whether it already ran.
     *
     * @return array{teams: int, players: int, seeded_at: string|null, seeded_by: string|null}
     */
    private function playerData(PlayerDataSeeder $seeder): array
    {
        $roster = $seeder->roster();
        $record = $seeder->record();

        return [
            'teams' => count($roster),
            'players' => array_sum(array_map(fn (array $team): int => count($team['players']), $roster)),
            'seeded_at' => $record?->created_at?->toIso8601String(),
            'seeded_by' => $record?->user?->name,
        ];
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
