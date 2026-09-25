<script setup lang="ts">
import { computed } from 'vue';
import { finishTypeFor } from '@/lib/finishTypes';

const props = withDefaults(
    defineProps<{
        score: number;
        isBurst: boolean;
        showPoints?: boolean;
    }>(),
    { showPoints: true },
);

const type = computed(() =>
    finishTypeFor({ score: props.score, is_burst: props.isBurst }),
);
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-md border border-border px-2 py-0.5 text-xs font-medium whitespace-nowrap"
    >
        <span class="size-1.5 rounded-full" :class="type.dot" />
        {{ type.label }}
        <span v-if="showPoints" class="font-mono text-muted-foreground"
            >+{{ type.points }}</span
        >
    </span>
</template>
