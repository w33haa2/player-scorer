<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { login } from '@/routes';
import { index as standings } from '@/routes/standings';

defineOptions({ inheritAttrs: false });

const awards = [
    {
        name: 'Finals MVP',
        icon: 'pi pi-star-fill',
        description: 'Highest accumulated score.',
        badge: 'from-amber-400 to-orange-500',
    },
    {
        name: 'Rookie of the Season',
        icon: 'pi pi-sparkles',
        description: 'Best newcomer in the scene.',
        badge: 'from-emerald-400 to-teal-500',
    },
    {
        name: 'Stamina King',
        icon: 'pi pi-heart-fill',
        description: 'Most spin finishes by a player.',
        badge: 'from-sky-400 to-blue-500',
    },
    {
        name: 'Over Lord',
        icon: 'pi pi-crown',
        description: 'Most over finishes by a player.',
        badge: 'from-violet-400 to-purple-500',
    },
    {
        name: 'Extreme Champion',
        icon: 'pi pi-bolt',
        description: 'Most extreme finishes by a player.',
        badge: 'from-rose-400 to-red-500',
    },
    {
        name: 'Burst God',
        icon: 'pi pi-flag-fill',
        description: 'Most burst finishes by a player.',
        badge: 'from-orange-400 to-amber-500',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="flex flex-col gap-16 pb-16">
        <!-- Hero -->
        <section
            class="relative flex flex-col items-center gap-6 py-20 text-center sm:py-28"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute top-1/2 left-1/2 h-[380px] w-[380px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-500/10 blur-[130px]"
            />

            <div class="relative flex flex-col items-center gap-6">
                <span
                    class="animate__animated animate__fadeInDown border-surface-200 bg-surface-0/80 text-surface-600 dark:border-surface-700 dark:bg-surface-900/80 dark:text-surface-300 inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium shadow-sm backdrop-blur"
                >
                    <span class="pi pi-bolt text-emerald-500" />
                    Davao Beyblade Battle League · Beyblade X
                </span>

                <h1
                    class="animate__animated animate__fadeInUp text-surface-900 max-w-3xl text-5xl font-extrabold tracking-tight sm:text-6xl dark:text-white"
                    style="animation-delay: 0.1s"
                >
                    DBBL Player Scorer
                </h1>

                <p
                    class="animate__animated animate__fadeInUp text-surface-500 dark:text-surface-400 max-w-xl text-lg"
                    style="animation-delay: 0.2s"
                >
                    The official companion app for tracking match finishes and
                    crowning the champions of the Davao Beyblade Battle League.
                </p>

                <div
                    class="animate__animated animate__fadeInUp mt-2 flex flex-wrap items-center justify-center gap-3"
                    style="animation-delay: 0.3s"
                >
                    <Link :href="standings()" prefetch>
                        <Button
                            label="View Current Standings"
                            icon="pi pi-trophy"
                            size="large"
                        />
                    </Link>
                    <Link :href="login()">
                        <Button
                            label="Admin Login"
                            icon="pi pi-sign-in"
                            size="large"
                            severity="secondary"
                            outlined
                        />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Awards -->
        <section class="flex flex-col gap-8">
            <div class="text-center">
                <h2 class="text-2xl font-bold sm:text-3xl">
                    Awards on the line
                </h2>
                <p class="text-surface-500 dark:text-surface-400 mt-2">
                    Six titles decided purely by the finishes.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="(award, index) in awards"
                    :key="award.name"
                    class="group animate__animated animate__fadeInUp border-surface-200 bg-surface-0/80 dark:border-surface-700 dark:bg-surface-900/80 flex items-center gap-4 rounded-2xl border p-5 shadow-sm backdrop-blur transition duration-300 hover:-translate-y-1 hover:border-emerald-500/40 hover:shadow-lg"
                    :style="{ animationDelay: `${0.1 + index * 0.07}s` }"
                >
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br text-white shadow-md transition-transform duration-300 group-hover:scale-110"
                        :class="award.badge"
                    >
                        <span :class="[award.icon, 'text-xl']" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold">{{ award.name }}</h3>
                        <p
                            class="text-surface-500 dark:text-surface-400 mt-0.5 text-sm"
                        >
                            {{ award.description }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
