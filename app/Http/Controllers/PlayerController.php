<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    /**
     * Display the list of players.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $direction = $request->string('direction')->lower()->toString() === 'desc' ? 'desc' : 'asc';
        $perPage = (int) min(max($request->integer('per_page', 10), 5), 100);

        $allowedSorts = ['blader_name', 'name', 'date_started', 'scores_count', 'scores_total'];
        $sort = $request->string('sort')->toString();
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'blader_name';

        $query = Player::query()
            ->with('teams')
            ->withCount('scores')
            ->withSum('scores as scores_total', 'score')
            ->when($search !== '', fn ($builder) => $builder->where(fn ($inner) => $inner
                ->whereLike('blader_name', "%{$search}%", caseSensitive: false)
                ->orWhereLike('name', "%{$search}%", caseSensitive: false)));

        // Rank matches by relevance (exact > starts-with > contains) when searching.
        if ($search !== '') {
            $query->orderByRaw(
                'CASE WHEN LOWER(blader_name) = LOWER(?) OR LOWER(name) = LOWER(?) THEN 0 '
                .'WHEN LOWER(blader_name) LIKE LOWER(?) OR LOWER(name) LIKE LOWER(?) THEN 1 ELSE 2 END',
                [$search, $search, $search.'%', $search.'%'],
            );
        }

        $players = $query
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Player $player): array => [
                'id' => $player->id,
                'name' => $player->name,
                'blader_name' => $player->blader_name,
                'team' => $player->currentTeam()?->summary(),
                'date_started' => $player->date_started?->toDateString(),
                'scores_count' => $player->scores_count,
                'scores_total' => (int) ($player->scores_total ?? 0),
            ]);

        return Inertia::render('players/Index', [
            'players' => $players,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Store a newly created player.
     */
    public function store(StorePlayerRequest $request): RedirectResponse
    {
        Player::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player added.')]);

        return to_route('players.index');
    }

    /**
     * Update an existing player.
     */
    public function update(UpdatePlayerRequest $request, Player $player): RedirectResponse
    {
        $player->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player updated.')]);

        return back();
    }

    /**
     * Delete a player and their scores.
     */
    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player deleted.')]);

        return back();
    }
}
