<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { ref } from 'vue';
import { getInitials } from '@/composables/useInitials';
import type { Award } from '@/types/scoring';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    awards: Award[];
    stats: {
        players: number;
        scores: number;
    };
}>();

const refreshing = ref(false);

function refresh(): void {
    router.reload({
        only: ['awards', 'stats'],
        onStart: () => (refreshing.value = true),
        onFinish: () => (refreshing.value = false),
    });
}

function pluralize(count: number, word: string): string {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}

const awardStyles: Record<string, { icon: string; badge: string }> = {
    finals_mvp: {
        icon: 'pi pi-star-fill',
        badge: 'from-amber-400 to-orange-500',
    },
    rookie_of_the_season: {
        icon: 'pi pi-sparkles',
        badge: 'from-emerald-400 to-teal-500',
    },
    stamina_king: {
        icon: 'pi pi-heart-fill',
        badge: 'from-sky-400 to-blue-500',
    },
    over_lord: { icon: 'pi pi-crown', badge: 'from-violet-400 to-purple-500' },
    extreme_champion: { icon: 'pi pi-bolt', badge: 'from-rose-400 to-red-500' },
    burst_god: {
        icon: 'pi pi-flag-fill',
        badge: 'from-orange-400 to-amber-500',
    },
};

function styleFor(key: string): { icon: string; badge: string } {
    return (
        awardStyles[key] ?? {
            icon: 'pi pi-trophy',
            badge: 'from-slate-400 to-slate-600',
        }
    );
}
</script>

<template>
    <Head title="Current Standings" />

    <div class="flex flex-col gap-10 py-2">
        <!-- Header -->
        <div class="relative flex flex-col items-center gap-3 text-center">
            <div
                aria-hidden="true"
                class="pointer-events-none absolute top-0 left-1/2 h-52 w-52 -translate-x-1/2 rounded-full bg-amber-500/10 blur-[100px]"
            />
            <div
                class="animate__animated animate__zoomIn relative flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-lg"
            >
                <span class="pi pi-trophy text-2xl" />
            </div>
            <h1 class="relative text-3xl font-bold sm:text-4xl">
                Current Standings
            </h1>
            <p class="text-surface-500 dark:text-surface-400 relative max-w-xl">
                Live award tallies for the round-robin tournament, aggregated
                from every recorded finish.
            </p>
            <div
                class="relative mt-1 flex flex-wrap items-center justify-center gap-3"
            >
                <Tag
                    :value="pluralize(props.stats.players, 'player')"
                    severity="secondary"
                    icon="pi pi-users"
                />
                <Tag
                    :value="pluralize(props.stats.scores, 'finish')"
                    severity="secondary"
                    icon="pi pi-list"
                />
                <Button
                    label="Refresh"
                    icon="pi pi-refresh"
                    size="small"
                    outlined
                    :loading="refreshing"
                    @click="refresh"
                />
            </div>
        </div>

        <!-- Award cards -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="(award, index) in props.awards"
                :key="award.key"
                class="group animate__animated animate__fadeInUp border-surface-200 bg-surface-0/80 dark:border-surface-700 dark:bg-surface-900/80 flex flex-col rounded-2xl border p-5 shadow-sm backdrop-blur transition duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-lg"
                :style="{ animationDelay: `${index * 0.07}s` }"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-md transition-transform duration-300 group-hover:scale-110"
                        :class="styleFor(award.key).badge"
                    >
                        <span :class="[styleFor(award.key).icon, 'text-lg']" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="leading-tight font-semibold">
                            {{ award.name }}
                        </h3>
                        <p
                            class="text-surface-500 dark:text-surface-400 truncate text-xs"
                        >
                            {{ award.description }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="award.winner"
                    class="bg-surface-100/80 dark:bg-surface-800/50 mt-4 flex items-center justify-between gap-3 rounded-xl p-3"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <Avatar
                            :label="getInitials(award.winner.player_name)"
                            shape="circle"
                            class="!bg-surface-0 !text-surface-700 dark:!bg-surface-700 dark:!text-surface-100 !size-9 shrink-0 !text-xs !font-semibold shadow-sm"
                        />
                        <div class="flex min-w-0 flex-col">
                            <span
                                class="truncate leading-tight font-semibold"
                                >{{ award.winner.player_name }}</span
                            >
                            <span
                                class="text-surface-500 dark:text-surface-400 text-xs"
                            >
                                {{ award.winner.value }} {{ award.metric }}
                            </span>
                        </div>
                    </div>
                    <span
                        class="pi pi-trophy shrink-0 text-lg text-amber-500"
                    />
                </div>
                <div
                    v-else
                    class="border-surface-300 text-surface-400 dark:border-surface-700 mt-4 flex items-center justify-center rounded-xl border border-dashed p-3 text-sm italic"
                >
                    Not awarded yet.
                </div>
            </div>
        </div>
    </div>
</template>
