<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { computed, ref, watch } from 'vue';
import BladerName from '@/components/BladerName.vue';
import ActivitySkeleton from '@/components/dashboard/ActivitySkeleton.vue';
import StatsSkeleton from '@/components/dashboard/StatsSkeleton.vue';
import FinishBadge from '@/components/FinishBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import TeamLogo from '@/components/TeamLogo.vue';
import TitleRaceSkeleton from '@/components/TitleRaceSkeleton.vue';
import { describeChangeParts, formatDateTime, timeAgo } from '@/lib/activity';
import { dashboard } from '@/routes';
import { index as auditLogs } from '@/routes/audit-logs';
import { index as players } from '@/routes/players';
import type { AuditLog, AwardLeaderboard } from '@/types/scoring';

// Deferred props: undefined until the follow-up request resolves them.
const props = defineProps<{
    stats?: {
        players: number;
        battles: number;
        points: number;
    };
    leaderboards?: AwardLeaderboard[];
    recentActivity?: AuditLog[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Overview', href: dashboard() }],
    },
});

const DEFERRED_KEYS = ['stats', 'leaderboards', 'recentActivity'];

// Keep skeletons up for a moment so a fast refresh doesn't flicker.
const MIN_SKELETON_MS = 350;

const refreshing = ref(false);
const updatedAt = ref<Date | null>(null);

// Stamp whenever fresh data lands (initial deferred load and refreshes).
watch(
    () => props.leaderboards,
    (value) => {
        if (value) {
            updatedAt.value = new Date();
        }
    },
);

// Each activity sentence split around the player, so the name can carry its tag.
const activity = computed(() =>
    (props.recentActivity ?? []).map((log) => ({
        ...log,
        sentence: describeChangeParts(log.changes),
    })),
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
</script>

<template>
    <Head title="Overview" />

    <div class="flex flex-col gap-8 p-4 sm:p-6">
        <PageHeader
            title="Overview"
            description="Who holds each title right now, and who's closest behind."
        >
            <template #actions>
                <span
                    v-if="updatedLabel"
                    class="hidden text-xs text-muted-foreground sm:inline"
                    aria-live="polite"
                    >Updated {{ updatedLabel }}</span
                >
                <Button
                    label="Refresh"
                    icon="pi pi-refresh"
                    severity="secondary"
                    outlined
                    size="small"
                    :loading="refreshing"
                    @click="refresh"
                />
                <Link :href="players()">
                    <Button label="Record scores" size="small" />
                </Link>
            </template>
        </PageHeader>

        <!-- League totals -->
        <Deferred data="stats">
            <template #fallback>
                <StatsSkeleton />
            </template>
            <template #default>
                <StatsSkeleton v-if="refreshing" />
                <dl
                    v-else-if="stats"
                    class="grid grid-cols-3 divide-x divide-border rounded-lg border border-border"
                >
                    <div class="px-4 py-3 sm:px-5 sm:py-4">
                        <dt class="text-xs text-muted-foreground">Players</dt>
                        <dd class="mt-1 font-mono text-2xl font-medium">
                            {{ stats.players }}
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-5 sm:py-4">
                        <dt class="text-xs text-muted-foreground">
                            Battles recorded
                        </dt>
                        <dd class="mt-1 font-mono text-2xl font-medium">
                            {{ stats.battles }}
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:px-5 sm:py-4">
                        <dt class="text-xs text-muted-foreground">
                            Points awarded
                        </dt>
                        <dd class="mt-1 font-mono text-2xl font-medium">
                            {{ stats.points }}
                        </dd>
                    </div>
                </dl>
            </template>
        </Deferred>

        <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <!-- Title race -->
            <section>
                <h2 class="mb-3 text-sm font-medium">Title race</h2>
                <Deferred data="leaderboards">
                    <template #fallback>
                        <TitleRaceSkeleton />
                    </template>
                    <template #default>
                        <TitleRaceSkeleton v-if="refreshing" />
                        <div
                            v-else-if="leaderboards"
                            class="grid gap-3 md:grid-cols-2"
                        >
                            <article
                                v-for="award in leaderboards"
                                :key="award.key"
                                class="rounded-lg border border-border bg-card"
                            >
                                <header
                                    class="border-b border-border px-4 py-3"
                                >
                                    <h3 class="text-sm font-semibold">
                                        {{ award.name }}
                                    </h3>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ award.description }}
                                    </p>
                                </header>

                                <ol
                                    v-if="award.leaders.length"
                                    class="px-4 py-2"
                                >
                                    <li
                                        v-for="(leader, rank) in award.leaders"
                                        :key="leader.player_id"
                                        class="flex items-center gap-3 py-1.5 text-sm"
                                    >
                                        <span
                                            class="w-4 font-mono text-xs"
                                            :class="
                                                rank === 0
                                                    ? 'text-highlight'
                                                    : 'text-muted-foreground'
                                            "
                                            >{{ rank + 1 }}</span
                                        >
                                        <TeamLogo :team="leader.team" />
                                        <BladerName
                                            :name="leader.player_name"
                                            :team="leader.team"
                                            class="min-w-0 flex-1 truncate"
                                            :class="
                                                rank === 0 ? 'font-medium' : ''
                                            "
                                        />
                                        <span
                                            class="font-mono text-xs text-muted-foreground"
                                            >{{ leader.value }}
                                            <span class="hidden sm:inline">{{
                                                award.metric
                                            }}</span></span
                                        >
                                    </li>
                                </ol>
                                <p
                                    v-else
                                    class="px-4 py-4 text-sm text-muted-foreground"
                                >
                                    No qualifying battles yet.
                                </p>
                            </article>
                        </div>
                    </template>
                </Deferred>
            </section>

            <!-- Recent activity -->
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-medium">Recent activity</h2>
                    <Link
                        :href="auditLogs()"
                        class="text-xs text-muted-foreground hover:text-foreground"
                        >View all</Link
                    >
                </div>

                <Deferred data="recentActivity">
                    <template #fallback>
                        <ActivitySkeleton />
                    </template>
                    <template #default>
                        <ActivitySkeleton v-if="refreshing" />
                        <template v-else-if="recentActivity">
                            <ul
                                v-if="recentActivity.length"
                                class="divide-y divide-border rounded-lg border border-border"
                            >
                                <li
                                    v-for="log in activity"
                                    :key="log.id"
                                    class="flex gap-3 px-4 py-3"
                                >
                                    <TeamLogo :team="log.team" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm">
                                            {{ log.sentence.before
                                            }}<BladerName
                                                :name="log.sentence.player"
                                                :team="log.team"
                                                class="font-medium"
                                            />{{ log.sentence.after }}
                                        </p>
                                        <div
                                            class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground"
                                        >
                                            <FinishBadge
                                                v-if="log.changes.new"
                                                :score="log.changes.new.score"
                                                :is-burst="
                                                    log.changes.new.is_burst
                                                "
                                                :show-points="false"
                                            />
                                            <span>{{ log.user_name }}</span>
                                            <span aria-hidden="true">·</span>
                                            <time
                                                :datetime="
                                                    log.created_at ?? undefined
                                                "
                                                :title="
                                                    formatDateTime(
                                                        log.created_at,
                                                    )
                                                "
                                                >{{
                                                    timeAgo(log.created_at)
                                                }}</time
                                            >
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <p
                                v-else
                                class="rounded-lg border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                Nothing yet. Recorded scores will show up here.
                            </p>
                        </template>
                    </template>
                </Deferred>
            </section>
        </div>
    </div>
</template>
