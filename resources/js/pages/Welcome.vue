<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { finishTypes } from '@/lib/finishTypes';
import { login } from '@/routes';
import { index as standings } from '@/routes/standings';

defineOptions({ inheritAttrs: false });

const page = usePage();

const titles = [
    {
        name: 'Finals MVP',
        description: 'Highest total points across all battles.',
    },
    {
        name: 'Rookie of the Season',
        description: 'Best newcomer in the scene.',
    },
    { name: 'Stamina King', description: 'Most spin finishes by a player.' },
    { name: 'Over Lord', description: 'Most over finishes by a player.' },
    {
        name: 'Extreme Champion',
        description: 'Most extreme finishes by a player.',
    },
    { name: 'Burst God', description: 'Most burst finishes by a player.' },
];
</script>

<template>
    <Head :title="page.props.seo.title ?? undefined" />

    <div class="flex flex-col gap-16 sm:gap-20">
        <!-- Intro -->
        <section
            class="grid items-center gap-8 pt-4 sm:pt-10 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-12"
        >
            <AppLogoIcon
                large
                fetchpriority="high"
                class="size-32 sm:size-40 lg:order-last lg:size-80 lg:justify-self-end"
            />
            <div class="max-w-2xl">
                <p class="text-sm text-muted-foreground">
                    Davao Beyblade Battle League
                </p>
                <h1
                    class="mt-3 text-4xl leading-[1.1] font-semibold tracking-tight sm:text-5xl"
                >
                    Scores and standings for the DBBL round robin.
                </h1>
                <p class="mt-5 text-base leading-relaxed text-muted-foreground">
                    Admins record the finish of every Beyblade X battle here.
                    Titles are worked out from those results automatically, so
                    the standings are always up to date.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <Link :href="standings()" prefetch>
                        <Button label="View standings" />
                    </Link>
                    <Link :href="login()" prefetch>
                        <Button
                            label="Admin sign in"
                            severity="secondary"
                            text
                        />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Scoring -->
        <section
            class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-12"
        >
            <div>
                <h2 class="text-lg font-semibold tracking-tight">
                    How scoring works
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Every battle ends in one of four finishes. Each is worth a
                    fixed number of points.
                </p>
            </div>
            <ul
                class="divide-y divide-border rounded-lg border border-border bg-card"
            >
                <li
                    v-for="finish in finishTypes"
                    :key="finish.key"
                    class="flex items-start gap-4 px-5 py-4"
                >
                    <span
                        class="mt-1.5 size-2 shrink-0 rounded-full"
                        :class="finish.dot"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium">
                            {{ finish.label }} finish
                        </p>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            {{ finish.description }}
                        </p>
                    </div>
                    <span class="font-mono text-sm text-muted-foreground"
                        >+{{ finish.points }}</span
                    >
                </li>
            </ul>
        </section>

        <!-- Titles -->
        <section
            class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-12"
        >
            <div>
                <h2 class="text-lg font-semibold tracking-tight">The titles</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Six titles are awarded at the end of the season.
                </p>
            </div>
            <dl
                class="grid overflow-hidden rounded-lg border border-border bg-card sm:grid-cols-2"
            >
                <div
                    v-for="title in titles"
                    :key="title.name"
                    class="border-b border-border px-5 py-4 last:border-b-0 sm:odd:border-r sm:[&:nth-last-child(-n+2)]:border-b-0"
                >
                    <dt class="text-sm font-medium">{{ title.name }}</dt>
                    <dd class="mt-0.5 text-sm text-muted-foreground">
                        {{ title.description }}
                    </dd>
                </div>
            </dl>
        </section>
    </div>
</template>
