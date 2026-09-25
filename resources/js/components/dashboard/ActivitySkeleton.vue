<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <ul
        class="divide-y divide-border rounded-lg border border-border"
        aria-hidden="true"
    >
        <li v-for="n in 5" :key="n" class="px-4 py-3">
            <Skeleton class="h-3.5 w-11/12" />
            <div class="mt-2.5 flex items-center gap-2">
                <Skeleton class="h-4 w-14" />
                <Skeleton class="h-3 w-24" />
            </div>
        </li>
    </ul>
</template>
