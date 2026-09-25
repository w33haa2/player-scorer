<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppWordmark from '@/components/AppWordmark.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh flex-col bg-background">
        <div class="flex items-center justify-between px-4 py-4 sm:px-6">
            <Link :href="home()" aria-label="DBBL Scorer home">
                <AppWordmark />
            </Link>
            <AppearanceToggle />
        </div>

        <div class="flex flex-1 items-center justify-center px-4 pb-16">
            <div class="w-full max-w-sm">
                <div class="mb-8">
                    <h1 class="text-xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
