<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Services\TournamentDay;
use App\Services\TournamentDays;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActionLogController extends Controller
{
    /**
     * Display the recent audit log entries.
     *
     * `?day={n}` shows only the changes made on that tournament day (by when
     * the change happened, so a Day 2 edit of a Day 1 score is under Day 2).
     */
    public function index(Request $request, TournamentDays $tournamentDays): Response
    {
        $search = trim((string) $request->string('search'));
        $perPage = (int) min(max($request->integer('per_page', 20), 5), 100);
        $day = $tournamentDays->find($request->integer('day'));

        $allowedActions = ['created', 'updated', 'deleted'];
        $action = $request->string('action')->toString();
        $action = in_array($action, $allowedActions, true) ? $action : null;

        $logs = ActionLog::query()
            ->with('user:id,name')
            ->onDay($day)
            ->when($action !== null, fn ($query) => $query->where('changes->action', $action))
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->whereLike('changes->player_name', "%{$search}%", caseSensitive: false)
                    ->orWhereHas('user', fn ($user) => $user->whereLike('name', "%{$search}%", caseSensitive: false));
            }))
            ->latest()
            // Scores saved together share a timestamp; keep them in a stable order.
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ActionLog $log): array => [
                'id' => $log->id,
                'user_name' => optional($log->user)->name ?? 'System',
                'changes' => $log->changes,
                'day' => $log->created_at ? $tournamentDays->dayOf($log->created_at)?->number : null,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('audit-logs/Index', [
            'logs' => $logs,
            'days' => fn (): array => array_map(fn (TournamentDay $tournamentDay): array => $tournamentDay->toArray(), $tournamentDays->all()),
            'filters' => [
                'day' => $day?->number,
                'search' => $search,
                'action' => $action,
                'per_page' => $perPage,
            ],
        ]);
    }
}
