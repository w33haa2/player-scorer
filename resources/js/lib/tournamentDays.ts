import type { TournamentDayOption } from '@/types/scoring';

/** "Oct 4" for a YYYY-MM-DD date, without shifting it across timezones. */
export function formatDayDate(date: string): string {
    return new Date(`${date}T00:00:00Z`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        timeZone: 'UTC',
    });
}

/** Query string for a day view; the whole tournament has none. */
export function dayQuery(day: number | null): { day?: number } {
    return day ? { day } : {};
}

export function findDay(
    days: TournamentDayOption[],
    day: number | null,
): TournamentDayOption | null {
    return days.find((option) => option.number === day) ?? null;
}
