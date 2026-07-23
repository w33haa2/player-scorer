<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScoreRequest;
use App\Http\Requests\UpdateScoreRequest;
use App\Models\Player;
use App\Models\PlayerScore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ScoreController extends Controller
{
    /**
     * Display all recorded scores.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $direction = $request->string('direction')->lower()->toString() === 'asc' ? 'asc' : 'desc';
        $perPage = (int) min(max($request->integer('per_page', 15), 5), 100);

        $allowedSorts = ['player_name', 'score', 'is_burst', 'created_at'];
        $sort = $request->string('sort')->toString();
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';

        $scoreFilter = $request->integer('score');
        $burstFilter = $request->string('is_burst')->toString();

        $query = PlayerScore::query()
            ->with('player:id,name')
            ->when($search !== '', fn ($q) => $q->whereHas(
                'player',
                fn ($player) => $player->where('name', 'like', "%{$search}%"),
            ))
            ->when(in_array($scoreFilter, [1, 2, 3], true), fn ($q) => $q->where('score', $scoreFilter))
            ->when($burstFilter !== '', fn ($q) => $q->where('is_burst', $burstFilter === '1' || $burstFilter === 'true'));

        if ($sort === 'player_name') {
            $query->orderBy(
                Player::select('name')->whereColumn('players.id', 'player_scores.player_id'),
                $direction,
            );
        } else {
            $query->orderBy($sort, $direction);
        }

        $scores = $query
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PlayerScore $score): array => [
                'id' => $score->id,
                'player_id' => $score->player_id,
                'player_name' => $score->player->name,
                'score' => $score->score,
                'is_burst' => $score->is_burst,
                'created_at' => $score->created_at?->toIso8601String(),
            ]);

        return Inertia::render('scores/Index', [
            'scores' => $scores,
            'filters' => [
                'search' => $search,
                'score' => in_array($scoreFilter, [1, 2, 3], true) ? $scoreFilter : null,
                'is_burst' => $burstFilter !== '' ? $burstFilter : null,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Store one or more scores for the given player.
     */
    public function store(StoreScoreRequest $request, Player $player): RedirectResponse
    {
        /** @var array<int, array{score: int, is_burst: bool}> $entries */
        $entries = $request->validated('scores');

        DB::transaction(function () use ($player, $entries): void {
            foreach ($entries as $entry) {
                $player->scores()->create([
                    'score' => $entry['score'],
                    'is_burst' => $entry['is_burst'],
                ]);
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice('{1} :count score added.|[2,*] :count scores added.', count($entries), ['count' => count($entries)]),
        ]);

        return back();
    }

    /**
     * Update a single score entry.
     */
    public function update(UpdateScoreRequest $request, PlayerScore $score): RedirectResponse
    {
        $score->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Score updated.')]);

        return back();
    }
}
