import type { ScoreEntry } from '@/types/scoring';

export type FinishKey = 'spin' | 'over' | 'burst' | 'extreme';

export type FinishType = {
    key: FinishKey;
    label: string;
    points: number;
    score: number;
    is_burst: boolean;
    /** Tailwind class for the small color dot that identifies the finish. */
    dot: string;
    description: string;
};

/** The four Beyblade X finishes and how they map onto (score, is_burst). */
export const finishTypes: FinishType[] = [
    {
        key: 'spin',
        label: 'Spin',
        points: 1,
        score: 1,
        is_burst: false,
        dot: 'bg-sky-500',
        description: 'The opposing bey stops spinning first.',
    },
    {
        key: 'over',
        label: 'Over',
        points: 2,
        score: 2,
        is_burst: false,
        dot: 'bg-violet-500',
        description: 'The opposing bey is knocked into an over zone.',
    },
    {
        key: 'burst',
        label: 'Burst',
        points: 2,
        score: 2,
        is_burst: true,
        dot: 'bg-amber-500',
        description: 'The opposing bey comes apart mid-battle.',
    },
    {
        key: 'extreme',
        label: 'Extreme',
        points: 3,
        score: 3,
        is_burst: false,
        dot: 'bg-rose-500',
        description: 'The opposing bey is knocked into the Xtreme zone.',
    },
];

export function finishKeyFor(
    entry: Pick<ScoreEntry, 'score' | 'is_burst'>,
): FinishKey {
    if (entry.is_burst) {
        return 'burst';
    }

    if (entry.score === 1) {
        return 'spin';
    }

    return entry.score === 3 ? 'extreme' : 'over';
}

export function finishTypeFor(
    entry: Pick<ScoreEntry, 'score' | 'is_burst'>,
): FinishType {
    const key = finishKeyFor(entry);

    return finishTypes.find((type) => type.key === key) ?? finishTypes[0];
}
