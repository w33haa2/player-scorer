<script setup lang="ts">
import Dialog from 'primevue/dialog';
import { computed, useId } from 'vue';
import BladerName from '@/components/BladerName.vue';
import FinishBadge from '@/components/FinishBadge.vue';
import TeamLogo from '@/components/TeamLogo.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDateTime, timeAgo } from '@/lib/activity';
import { finishTypes } from '@/lib/finishTypes';
import type { PlayerProfile, TeamSummary } from '@/types/scoring';

const props = defineProps<{
    /** Loaded profile, or null while it's being fetched. */
    player: PlayerProfile | null;
    /** Name and team from the clicked row, shown immediately while loading. */
    fallbackName: string | null;
    fallbackTeam: TeamSummary | null;
    loading: boolean;
    missing: boolean;
}>();

const visible = defineModel<boolean>('visible', { required: true });

const emit = defineEmits<{ hide: [] }>();

const titleId = useId();

const headerName = computed(
    () => props.player?.player_name ?? props.fallbackName ?? 'Player',
);

const headerTeam = computed(() =>
    props.player ? props.player.team : props.fallbackTeam,
);

const startedLabel = computed(() => {
    if (!props.player?.date_started) {
        return null;
    }

    return new Date(props.player.date_started).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
});

const subtitle = computed(() =>
    [
        headerTeam.value?.name,
        startedLabel.value && `Started ${startedLabel.value}`,
    ]
        .filter(Boolean)
        .join(' · '),
);

const totals = computed(() => {
    const player = props.player;

    if (!player) {
        return [];
    }

    return [
        {
            label: 'Rank',
            value: player.rank !== null ? `#${player.rank}` : '—',
            suffix: player.rank !== null ? `of ${player.total_players}` : null,
            highlight: player.rank === 1,
        },
        { label: 'Points', value: String(player.points) },
        { label: 'Battles', value: String(player.battles) },
        { label: 'Avg / battle', value: player.average.toFixed(2) },
    ];
});

const breakdownRows = computed(() => {
    const player = props.player;

    if (!player) {
        return [];
    }

    return finishTypes.map((type) => {
        const count = player.breakdown[type.key];

        return {
            ...type,
            count,
            percent:
                player.battles > 0
                    ? Math.round((count / player.battles) * 100)
                    : 0,
        };
    });
});

function ordinal(position: number): string {
    const suffix = { 1: 'st', 2: 'nd', 3: 'rd' }[position] ?? 'th';

    return `${position}${suffix}`;
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :aria-labelledby="titleId"
        :style="{ width: '95vw', maxWidth: '36rem' }"
        :draggable="false"
        dismissable-mask
        @hide="emit('hide')"
    >
        <template #header>
            <div class="flex min-w-0 items-center gap-3">
                <TeamLogo v-if="headerTeam" :team="headerTeam" size="lg" />
                <div class="min-w-0">
                    <h2
                        :id="titleId"
                        class="truncate text-lg leading-snug font-semibold"
                    >
                        <BladerName :name="headerName" :team="headerTeam" />
                    </h2>
                    <p
                        v-if="subtitle"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ subtitle }}
                    </p>
                </div>
            </div>
        </template>

        <!-- Not found -->
        <p
            v-if="missing"
            class="py-6 text-center text-sm text-muted-foreground"
        >
            This player couldn't be found. They may have been removed.
        </p>

        <!-- Loading -->
        <div
            v-else-if="loading || !player"
            class="flex flex-col gap-7 pt-1"
            aria-busy="true"
        >
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="rounded-lg border border-border px-3 py-3"
                >
                    <Skeleton class="h-3 w-12" />
                    <Skeleton class="mt-2.5 h-6 w-10" />
                </div>
            </div>
            <div class="flex flex-col gap-3">
                <Skeleton class="h-4 w-16" />
                <Skeleton class="h-2 w-full rounded-full" />
                <div class="grid grid-cols-2 gap-x-6 gap-y-2.5">
                    <Skeleton v-for="n in 4" :key="n" class="h-4 w-full" />
                </div>
            </div>
            <div class="flex flex-col gap-3">
                <Skeleton class="h-4 w-28" />
                <Skeleton class="h-11 w-full" />
            </div>
        </div>

        <!-- Loaded -->
        <div v-else class="flex flex-col gap-7 pt-1">
            <!-- Totals -->
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    v-for="stat in totals"
                    :key="stat.label"
                    class="rounded-lg border border-border px-3 py-3"
                >
                    <dt class="text-xs text-muted-foreground">
                        {{ stat.label }}
                    </dt>
                    <dd
                        class="mt-1 flex items-baseline gap-1 font-mono text-xl font-medium"
                        :class="stat.highlight ? 'text-highlight' : ''"
                    >
                        {{ stat.value }}
                        <span
                            v-if="stat.suffix"
                            class="text-xs font-normal text-muted-foreground"
                            >{{ stat.suffix }}</span
                        >
                    </dd>
                </div>
            </dl>

            <!-- Finish breakdown -->
            <section>
                <h3 class="mb-3 text-sm font-medium">Finishes</h3>
                <div
                    class="flex h-2 overflow-hidden rounded-full bg-secondary"
                    role="img"
                    :aria-label="
                        breakdownRows
                            .map((row) => `${row.label} ${row.count}`)
                            .join(', ')
                    "
                >
                    <div
                        v-for="row in breakdownRows"
                        :key="row.key"
                        :class="row.dot"
                        :style="{ width: `${row.percent}%` }"
                    />
                </div>
                <ul class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2">
                    <li
                        v-for="row in breakdownRows"
                        :key="row.key"
                        class="flex items-center gap-2 text-sm"
                    >
                        <span class="size-1.5 rounded-full" :class="row.dot" />
                        <span class="flex-1">{{ row.label }}</span>
                        <span class="font-mono">{{ row.count }}</span>
                        <span
                            class="w-9 text-right font-mono text-xs text-muted-foreground"
                            >{{ row.percent }}%</span
                        >
                    </li>
                </ul>
            </section>

            <!-- Title placements -->
            <section>
                <h3 class="mb-3 text-sm font-medium">Title placements</h3>
                <ul
                    v-if="player.placements.length"
                    class="divide-y divide-border rounded-lg border border-border"
                >
                    <li
                        v-for="placement in player.placements"
                        :key="placement.key"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm"
                    >
                        <span
                            class="w-14 shrink-0 text-xs font-medium"
                            :class="
                                placement.position === 1
                                    ? 'text-highlight'
                                    : 'text-muted-foreground'
                            "
                            >{{
                                placement.position === 1
                                    ? 'Holder'
                                    : ordinal(placement.position)
                            }}</span
                        >
                        <span class="min-w-0 flex-1 truncate">{{
                            placement.name
                        }}</span>
                        <span class="font-mono text-xs text-muted-foreground"
                            >{{ placement.value }} {{ placement.metric }}</span
                        >
                    </li>
                </ul>
                <p
                    v-else
                    class="rounded-lg border border-dashed border-border px-3 py-4 text-center text-sm text-muted-foreground"
                >
                    Not in the top 3 of any title yet.
                </p>
            </section>

            <!-- Recent battles -->
            <section>
                <h3 class="mb-3 text-sm font-medium">Recent battles</h3>
                <ul
                    v-if="player.recent.length"
                    class="divide-y divide-border rounded-lg border border-border"
                >
                    <li
                        v-for="battle in player.recent"
                        :key="battle.id"
                        class="flex items-center justify-between gap-3 px-3 py-2"
                    >
                        <FinishBadge
                            :score="battle.score"
                            :is-burst="battle.is_burst"
                        />
                        <time
                            class="text-xs text-muted-foreground"
                            :datetime="battle.created_at ?? undefined"
                            :title="formatDateTime(battle.created_at)"
                            >{{ timeAgo(battle.created_at) }}</time
                        >
                    </li>
                </ul>
                <p
                    v-else
                    class="rounded-lg border border-dashed border-border px-3 py-4 text-center text-sm text-muted-foreground"
                >
                    No battles recorded yet.
                </p>
            </section>
        </div>
    </Dialog>
</template>
