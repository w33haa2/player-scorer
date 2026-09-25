<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppWordmark from '@/components/AppWordmark.vue';
import ParticleNetwork from '@/components/ParticleNetwork.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, home, login } from '@/routes';
import { index as standings } from '@/routes/standings';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="relative flex min-h-svh flex-col bg-background text-foreground">
        <ParticleNetwork fullscreen />

        <header class="relative z-10 border-b border-border bg-background">
            <div
                class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6"
            >
                <Link :href="home()" prefetch aria-label="DBBL Scorer home">
                    <AppWordmark />
                </Link>

                <nav class="flex items-center gap-1">
                    <Link
                        :href="standings()"
                        prefetch
                        class="rounded-md px-3 py-2 text-sm transition-colors hover:text-foreground"
                        :class="
                            isCurrentUrl(standings())
                                ? 'text-foreground'
                                : 'text-muted-foreground'
                        "
                    >
                        Standings
                    </Link>
                    <AppearanceToggle />
                    <Link
                        :href="page.props.auth?.user ? dashboard() : login()"
                        prefetch
                        class="ml-1"
                    >
                        <Button
                            :label="
                                page.props.auth?.user ? 'Dashboard' : 'Sign in'
                            "
                            size="small"
                            severity="secondary"
                            outlined
                        />
                    </Link>
                </nav>
            </div>
        </header>

        <main
            class="relative z-10 mx-auto w-full max-w-5xl flex-1 px-4 py-10 sm:px-6 sm:py-14"
        >
            <slot />
        </main>

        <footer class="relative z-10 border-t border-border bg-background">
            <div
                class="mx-auto flex max-w-5xl flex-col gap-1 px-4 py-6 text-xs text-muted-foreground sm:flex-row sm:justify-between sm:px-6"
            >
                <span>Davao Beyblade Battle League</span>
                <span
                    >Scores are recorded by league admins after each
                    match.</span
                >
            </div>
        </footer>
    </div>
</template>
