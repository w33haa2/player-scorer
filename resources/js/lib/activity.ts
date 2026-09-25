import { finishTypeFor } from '@/lib/finishTypes';
import type { AuditLogChange, ScoreEntry } from '@/types/scoring';

function finishLabel(entry: ScoreEntry | null): string {
    if (!entry) {
        return 'a score';
    }

    const type = finishTypeFor(entry);

    return `${type.label} (+${type.points})`;
}

/** Plain-language summary of a score change for the activity feed. */
export function describeChange(change: AuditLogChange): string {
    const player = change.player_name ?? `player #${change.player_id}`;

    switch (change.action) {
        case 'created':
            return `Recorded ${finishLabel(change.new)} for ${player}`;
        case 'updated':
            return `Changed ${player}'s ${finishLabel(change.old)} to ${finishLabel(change.new)}`;
        case 'deleted':
            return `Removed ${finishLabel(change.old)} from ${player}`;
        default:
            return `Updated a score for ${player}`;
    }
}

const relativeFormatter = new Intl.RelativeTimeFormat(undefined, {
    numeric: 'auto',
});

/** "just now", "5 minutes ago", "yesterday", … */
export function timeAgo(value: string | null): string {
    if (!value) {
        return '—';
    }

    const seconds = Math.round((new Date(value).getTime() - Date.now()) / 1000);
    const abs = Math.abs(seconds);

    if (abs < 45) {
        return 'just now';
    }

    if (abs < 3600) {
        return relativeFormatter.format(Math.round(seconds / 60), 'minute');
    }

    if (abs < 86400) {
        return relativeFormatter.format(Math.round(seconds / 3600), 'hour');
    }

    if (abs < 86400 * 7) {
        return relativeFormatter.format(Math.round(seconds / 86400), 'day');
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

/** Full timestamp for tooltips / table cells. */
export function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}
