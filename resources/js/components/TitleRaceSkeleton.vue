<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="grid gap-3 md:grid-cols-2" aria-hidden="true">
        <div
            v-for="n in 6"
            :key="n"
            class="rounded-lg border border-border bg-card"
        >
            <div class="border-b border-border px-4 py-3">
                <Skeleton class="h-4 w-32" />
                <Skeleton class="mt-2 h-3 w-48 max-w-full" />
            </div>
            <div class="px-4 py-2">
                <div
                    v-for="row in 3"
                    :key="row"
                    class="flex h-8 items-center gap-3"
                >
                    <Skeleton class="h-3 w-3" />
                    <Skeleton
                        class="h-3.5 flex-1"
                        :class="row === 1 ? 'max-w-40' : 'max-w-32'"
                    />
                    <Skeleton class="h-3 w-10" />
                </div>
            </div>
        </div>
    </div>
</template>
