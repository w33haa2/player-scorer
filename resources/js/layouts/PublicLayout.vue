<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ParticleNetwork from '@/components/ParticleNetwork.vue';
import { dashboard, login } from '@/routes';

const page = usePage();
</script>

<template>
    <div
        class="bg-surface-50 text-surface-900 dark:bg-surface-950 dark:text-surface-0 relative min-h-svh"
    >
        <ParticleNetwork fullscreen />

        <header
            class="border-surface-200 bg-surface-0 dark:border-surface-800 dark:bg-surface-900 relative z-10 border-b"
        >
            <div
                class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4"
            >
                <Link
                    :href="dashboard()"
                    prefetch
                    class="flex items-center gap-2 font-semibold"
                >
                    <AppLogoIcon class="size-7 fill-current" />
                    <span>DBBL Player Scorer</span>
                </Link>

                <Link
                    :href="page.props.auth?.user ? dashboard() : login()"
                    prefetch
                >
                    <Button
                        :label="
                            page.props.auth?.user ? 'Dashboard' : 'Admin Login'
                        "
                        icon="pi pi-sign-in"
                        size="small"
                        outlined
                    />
                </Link>
            </div>
        </header>

        <main class="relative z-10 mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>
