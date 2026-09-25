<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { computed, ref, useTemplateRef, watch } from 'vue';
import BladerName from '@/components/BladerName.vue';
import PlayerStatsDialog from '@/components/standings/PlayerStatsDialog.vue';
import TeamLogo from '@/components/TeamLogo.vue';
import TitleRaceSkeleton from '@/components/TitleRaceSkeleton.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { finishTypes } from '@/lib/finishTypes';
import { index as standings } from '@/routes/standings';
import type {
    AwardLeaderboard,
    LeaderboardRow,
    PlayerProfile,
    TeamSummary,
} from '@/types/scoring';

defineOptions({ inheritAttrs: false });

// Deferred props: undefined until the follow-up request resolves them.
const props = defineProps<{
    awards?: AwardLeaderboard[];
    leaderboard?: LeaderboardRow[];
    stats?: {
        players: number;
        scores: number;
    };
    selectedPlayer: PlayerProfile | null;
}>();

/* ------------------------------------------------------------------ */
/* Refresh                                                             */
/* ------------------------------------------------------------------ */

const DEFERRED_KEYS = ['awards', 'leaderboard', 'stats'];

// Keep skeletons up for a moment so a fast refresh doesn't flicker.
const MIN_SKELETON_MS = 350;

// Rows to sketch before the leaderboard has loaded for the first time.
const INITIAL_SKELETON_ROWS = 8;

const refreshing = ref(false);
const updatedAt = ref<Date | null>(null);

// Stamp whenever fresh data lands (initial deferred load and refreshes).
watch(
    () => props.leaderboard,
    (value) => {
        if (value) {
            updatedAt.value = new Date();
        }
    },
    { immediate: true },
);

const updatedLabel = computed(() =>
    updatedAt.value
        ? updatedAt.value.toLocaleTimeString(undefined, {
              hour: 'numeric',
              minute: '2-digit',
          })
        : null,
);

function refresh(): void {
    if (refreshing.value) {
        return;
    }

    const startedAt = Date.now();

    router.reload({
        only: DEFERRED_KEYS,
        onStart: () => {
            refreshing.value = true;
        },
        onFinish: () => {
            const remaining = Math.max(
                0,
                MIN_SKELETON_MS - (Date.now() - startedAt),
            );

            setTimeout(() => {
                refreshing.value = false;
            }, remaining);
        },
    });
}

function plural(count: number, word: string): string {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

/* ------------------------------------------------------------------ */
/* Leaderboard sorting                                                 */
/* ------------------------------------------------------------------ */

type SortKey = 'points' | 'battles' | 'spin' | 'over' | 'burst' | 'extreme';

type Column = {
    key: SortKey;
    label: string;
    dot?: string;
    /** Tailwind visibility classes so narrow screens stay readable. */
    visibility: string;
};

const columns: Column[] = [
    { key: 'battles', label: 'Battles', visibility: 'hidden sm:table-cell' },
    ...finishTypes.map((type) => ({
        key: type.key as SortKey,
        label: type.label,
        dot: type.dot,
        visibility: 'hidden md:table-cell',
    })),
    { key: 'points', label: 'Points', visibility: '' },
];

const sortKey = ref<SortKey>('points');

// Sorted by the chosen stat, with competition ranking (ties share a rank).
const rows = computed(() => {
    const key = sortKey.value;
    const sorted = [...(props.leaderboard ?? [])].sort(
        (a, b) =>
            b[key] - a[key] ||
            b.points - a.points ||
            a.player_name.localeCompare(b.player_name),
    );

    let position = 0;
    let previous: number | null = null;

    return sorted.map((row, index) => {
        if (row[key] !== previous) {
            position = index + 1;
            previous = row[key];
        }

        return { ...row, position };
    });
});

/* ------------------------------------------------------------------ */
/* Leaderboard search (client-side: the full leaderboard is loaded)    */
/* ------------------------------------------------------------------ */

const search = ref('');
const searchInput = useTemplateRef<HTMLInputElement>('searchInput');

const searchTerms = computed(() =>
    search.value.trim().toLowerCase().split(/\s+/).filter(Boolean),
);

// Filtered after ranking, so matches keep their real position. Every word
// must match the acronym, blader name or team name ("dnv fer" works).
const visibleRows = computed(() => {
    const terms = searchTerms.value;

    if (!terms.length) {
        return rows.value;
    }

    return rows.value.filter((row) => {
        const haystack = [row.team?.acronym, row.player_name, row.team?.name]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return terms.every((term) => haystack.includes(term));
    });
});

const searchSummary = computed(() =>
    searchTerms.value.length
        ? `${visibleRows.value.length} of ${rows.value.length}`
        : '',
);

function clearSearch(): void {
    search.value = '';
    searchInput.value?.focus();
}

// Match the visible row count on refresh so the page height doesn't jump.
const skeletonRowCount = computed(
    () => visibleRows.value.length || INITIAL_SKELETON_ROWS,
);

// These bladers get a glowing rainbow outline on their leaderboard row and a
// flaming name wherever they appear on this page. Matched case-insensitively.
const FEATURED_BLADERS = new Set([
    'xetty',
    'jiyo',
    'zxy',
    'ferrari_430',
    'tito j',
]);

function isFeatured(player: { player_name: string }): boolean {
    return FEATURED_BLADERS.has(player.player_name.toLowerCase());
}

function ariaSort(key: SortKey): 'descending' | 'none' {
    return sortKey.value === key ? 'descending' : 'none';
}

/* ------------------------------------------------------------------ */
/* Player dialog (URL-driven: /standings?player=ID)                    */
/* ------------------------------------------------------------------ */

const dialogVisible = ref(props.selectedPlayer !== null);
const requestedId = ref<number | null>(props.selectedPlayer?.player_id ?? null);
const requestedName = ref<string | null>(
    props.selectedPlayer?.player_name ?? null,
);
const requestedTeam = ref<TeamSummary | null>(
    props.selectedPlayer?.team ?? null,
);
// Keeps the last loaded profile so content doesn't vanish during close.
const displayedPlayer = ref<PlayerProfile | null>(props.selectedPlayer);
const playerMissing = ref(false);

watch(
    () => props.selectedPlayer,
    (value) => {
        if (value) {
            displayedPlayer.value = value;
        }
    },
);

const isLoadingPlayer = computed(
    () =>
        !playerMissing.value &&
        displayedPlayer.value?.player_id !== requestedId.value,
);

// Matches the server-rendered titles (StandingsController) for each state.
const pageTitle = computed(() =>
    dialogVisible.value && requestedName.value
        ? `${requestedName.value} player stats`
        : 'Standings & title race',
);

function openPlayer(player: {
    player_id: number;
    player_name: string;
    team: TeamSummary | null;
}): void {
    requestedId.value = player.player_id;
    requestedName.value = player.player_name;
    requestedTeam.value = player.team;
    playerMissing.value = false;
    dialogVisible.value = true;

    router.get(
        standings().url,
        { player: player.player_id },
        {
            only: ['selectedPlayer'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: (page) => {
                playerMissing.value = !page.props.selectedPlayer;
            },
        },
    );
}

function onDialogHide(): void {
    requestedId.value = null;

    router.get(
        standings().url,
        {},
        {
            only: ['selectedPlayer'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}
</script>

<template>
    <Head :title="pageTitle" />

    <div class="flex flex-col gap-12">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Standings</h1>
                <!-- A div, not a <p>: the inline skeleton renders a <div>. -->
                <div class="mt-2 max-w-lg text-sm text-muted-foreground">
                    Season leaderboard ·
                    <template v-if="stats && !refreshing"
                        >{{ plural(stats.players, 'player') }},
                        {{ plural(stats.scores, 'battle') }}.</template
                    >
                    <Skeleton
                        v-else
                        class="inline-block h-3.5 w-40 align-middle"
                        aria-hidden="true"
                    />
                    Select a player to see their stats.
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span
                    v-if="updatedLabel"
                    class="text-xs text-muted-foreground"
                    aria-live="polite"
                    >Updated {{ updatedLabel }}</span
                >
                <Button
                    label="Refresh"
                    icon="pi pi-refresh"
                    size="small"
                    severity="secondary"
                    outlined
                    :loading="refreshing"
                    @click="refresh"
                />
            </div>
        </header>

        <!-- Title race -->
        <section :aria-busy="refreshing || !awards">
            <h2 class="mb-3 text-sm font-medium">Title race</h2>
            <TitleRaceSkeleton
                v-if="refreshing || !awards"
                class="lg:grid-cols-3"
            />
            <div v-else class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="award in awards"
                    :key="award.key"
                    class="rounded-lg border border-border bg-card"
                >
                    <header class="border-b border-border px-4 py-3">
                        <h3 class="text-sm font-semibold">{{ award.name }}</h3>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ award.description }}
                        </p>
                    </header>

                    <ol v-if="award.leaders.length" class="px-2 py-2">
                        <li
                            v-for="(leader, index) in award.leaders"
                            :key="leader.player_id"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-md px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent focus-visible:outline-2 focus-visible:outline-ring"
                                @click="openPlayer(leader)"
                            >
                                <span
                                    class="w-4 font-mono text-xs"
                                    :class="
                                        index === 0
                                            ? 'text-highlight'
                                            : 'text-muted-foreground'
                                    "
                                    >{{ index + 1 }}</span
                                >
                                <TeamLogo :team="leader.team" />
                                <BladerName
                                    :name="leader.player_name"
                                    :team="leader.team"
                                    :flame="isFeatured(leader)"
                                    class="min-w-0 flex-1 break-words"
                                    :class="index === 0 ? 'font-medium' : ''"
                                />
                                <span
                                    class="font-mono text-xs text-muted-foreground"
                                    >{{ leader.value }}</span
                                >
                            </button>
                        </li>
                    </ol>
                    <p v-else class="px-4 py-4 text-sm text-muted-foreground">
                        No holder yet.
                    </p>
                </article>
            </div>
        </section>

        <!-- Leaderboard -->
        <section :aria-busy="refreshing || !leaderboard">
            <div class="mb-3 flex items-baseline justify-between gap-3">
                <h2 class="text-sm font-medium">Leaderboard</h2>
                <p class="text-xs text-muted-foreground">
                    Sorted by
                    {{
                        columns.find((column) => column.key === sortKey)?.label
                    }}
                </p>
            </div>

            <!-- @container: featured rows size their outline to this card. -->
            <div
                class="@container overflow-hidden rounded-lg border border-border bg-card"
            >
                <!-- Search sits inside the card as the table's first row. -->
                <label
                    class="flex items-center gap-3 border-b border-border px-4 transition-colors focus-within:bg-accent/40"
                >
                    <span
                        class="pi pi-search text-xs text-muted-foreground"
                        aria-hidden="true"
                    />
                    <input
                        ref="searchInput"
                        v-model="search"
                        type="search"
                        placeholder="Search bladers or teams"
                        aria-label="Search the leaderboard"
                        autocomplete="off"
                        enterkeyhint="search"
                        class="h-11 min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground [&::-webkit-search-cancel-button]:appearance-none"
                        @keydown.esc="search = ''"
                    />
                    <template v-if="searchTerms.length">
                        <span
                            class="shrink-0 font-mono text-xs text-muted-foreground"
                            aria-hidden="true"
                            >{{ searchSummary }}</span
                        >
                        <button
                            type="button"
                            class="-mr-1.5 flex size-7 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring"
                            aria-label="Clear search"
                            @click.prevent="clearSearch"
                        >
                            <span class="pi pi-times text-xs" />
                        </button>
                    </template>
                    <span class="sr-only" aria-live="polite">{{
                        searchSummary ? `${searchSummary} bladers` : ''
                    }}</span>
                </label>

                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-border text-xs text-muted-foreground"
                        >
                            <th
                                scope="col"
                                class="w-12 px-4 py-2.5 text-left font-medium"
                            >
                                #
                            </th>
                            <!-- The player column takes the spare width, so names are never cut off. -->
                            <th
                                scope="col"
                                class="w-full px-4 py-2.5 text-left font-medium"
                            >
                                Player
                            </th>
                            <th
                                v-for="column in columns"
                                :key="column.key"
                                scope="col"
                                class="w-24 px-3 py-2.5 text-right font-medium whitespace-nowrap"
                                :class="column.visibility"
                                :aria-sort="ariaSort(column.key)"
                            >
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 transition-colors hover:text-foreground"
                                    :class="
                                        sortKey === column.key
                                            ? 'text-foreground'
                                            : ''
                                    "
                                    @click="sortKey = column.key"
                                >
                                    <span
                                        v-if="column.dot"
                                        class="size-1.5 rounded-full"
                                        :class="column.dot"
                                    />
                                    {{ column.label }}
                                    <span
                                        class="pi pi-arrow-down text-[9px]"
                                        :class="
                                            sortKey === column.key
                                                ? 'opacity-100'
                                                : 'opacity-0'
                                        "
                                    />
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        v-if="refreshing || !leaderboard"
                        class="divide-y divide-border"
                        aria-hidden="true"
                    >
                        <!-- h-11 matches a real row, so the swap doesn't shift. -->
                        <tr v-for="n in skeletonRowCount" :key="n" class="h-11">
                            <td class="px-4">
                                <Skeleton class="h-3 w-4" />
                            </td>
                            <td class="px-4">
                                <div class="flex items-center gap-2.5">
                                    <Skeleton
                                        class="size-5 shrink-0 rounded-sm"
                                    />
                                    <Skeleton
                                        class="h-3.5 w-full"
                                        :class="n % 2 ? 'max-w-40' : 'max-w-28'"
                                    />
                                </div>
                            </td>
                            <td
                                v-for="column in columns"
                                :key="column.key"
                                class="px-3"
                                :class="column.visibility"
                            >
                                <Skeleton class="ml-auto h-3.5 w-6" />
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else class="divide-y divide-border">
                        <tr
                            v-for="row in visibleRows"
                            :key="row.player_id"
                            class="cursor-pointer transition-colors hover:bg-accent/60"
                            @click="openPlayer(row)"
                        >
                            <td
                                class="px-4 py-3 font-mono text-xs"
                                :class="[
                                    row.position === 1
                                        ? 'font-medium text-highlight'
                                        : row.position <= 3
                                          ? 'text-foreground'
                                          : 'text-muted-foreground',
                                    { relative: isFeatured(row) },
                                ]"
                            >
                                <span
                                    v-if="isFeatured(row)"
                                    class="rainbow-outline"
                                    aria-hidden="true"
                                />
                                {{ row.position }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <TeamLogo :team="row.team" />
                                    <button
                                        type="button"
                                        class="text-left font-medium break-words hover:underline focus-visible:underline focus-visible:outline-none"
                                        @click.stop="openPlayer(row)"
                                    >
                                        <BladerName
                                            :name="row.player_name"
                                            :team="row.team"
                                            :flame="isFeatured(row)"
                                        />
                                    </button>
                                </div>
                            </td>
                            <td
                                v-for="column in columns"
                                :key="column.key"
                                class="px-3 py-3 text-right font-mono"
                                :class="[
                                    column.visibility,
                                    column.key === 'points'
                                        ? 'font-medium text-foreground'
                                        : sortKey === column.key
                                          ? 'text-foreground'
                                          : 'text-muted-foreground',
                                ]"
                            >
                                {{ row[column.key] }}
                            </td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td
                                :colspan="columns.length + 2"
                                class="px-4 py-10 text-center text-sm text-muted-foreground"
                            >
                                No players yet.
                            </td>
                        </tr>
                        <tr v-else-if="!visibleRows.length">
                            <td
                                :colspan="columns.length + 2"
                                class="px-4 py-10 text-center text-sm text-muted-foreground"
                            >
                                No bladers or teams match “{{ search.trim() }}”.
                                <button
                                    type="button"
                                    class="ml-1 font-medium text-foreground hover:underline"
                                    @click="clearSearch"
                                >
                                    Clear search
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-muted-foreground md:hidden">
                Rotate or widen your screen to see the finish breakdown.
            </p>
        </section>
    </div>

    <PlayerStatsDialog
        v-model:visible="dialogVisible"
        :player="isLoadingPlayer ? null : displayedPlayer"
        :fallback-name="requestedName"
        :fallback-team="requestedTeam"
        :loading="isLoadingPlayer"
        :missing="playerMissing"
        @hide="onDialogHide"
    />
</template>
