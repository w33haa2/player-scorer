<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActionLogController extends Controller
{
    /**
     * Display the recent audit log entries.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $perPage = (int) min(max($request->integer('per_page', 20), 5), 100);

        $allowedActions = ['created', 'updated', 'deleted'];
        $action = $request->string('action')->toString();
        $action = in_array($action, $allowedActions, true) ? $action : null;

        $logs = ActionLog::query()
            ->with('user:id,name')
            ->when($action !== null, fn ($query) => $query->where('changes->action', $action))
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('changes->player_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ActionLog $log): array => [
                'id' => $log->id,
                'user_name' => optional($log->user)->name ?? 'System',
                'changes' => $log->changes,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('audit-logs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'action' => $action,
                'per_page' => $perPage,
            ],
        ]);
    }
}
