<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div
        class="grid grid-cols-3 divide-x divide-border rounded-lg border border-border"
        aria-hidden="true"
    >
        <div v-for="n in 3" :key="n" class="px-4 py-3 sm:px-5 sm:py-4">
            <Skeleton class="h-3 w-20 max-w-full" />
            <Skeleton class="mt-2.5 h-7 w-12" />
        </div>
    </div>
</template>
