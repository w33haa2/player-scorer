<script setup lang="ts">
import type { FinishType } from '@/lib/finishTypes';
import { finishKeyFor, finishTypes } from '@/lib/finishTypes';

defineProps<{
    label: string;
}>();

const score = defineModel<number>('score', { required: true });
const isBurst = defineModel<boolean>('isBurst', { required: true });

function isSelected(type: FinishType): boolean {
    return (
        finishKeyFor({ score: score.value, is_burst: isBurst.value }) ===
        type.key
    );
}

function select(type: FinishType): void {
    score.value = type.score;
    isBurst.value = type.is_burst;
}

// Arrow keys move the selection within the group (standard radio behavior).
function onKeydown(event: KeyboardEvent): void {
    const keys = ['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'];

    if (!keys.includes(event.key)) {
        return;
    }

    event.preventDefault();

    const current = finishTypes.findIndex((type) => isSelected(type));
    const step =
        event.key === 'ArrowRight' || event.key === 'ArrowDown' ? 1 : -1;
    const next = (current + step + finishTypes.length) % finishTypes.length;

    select(finishTypes[next]);

    const group = event.currentTarget as HTMLElement;
    group.querySelectorAll<HTMLButtonElement>('[role="radio"]')[next]?.focus();
}
</script>

<template>
    <div
        role="radiogroup"
        :aria-label="label"
        class="grid grid-cols-2 gap-2 sm:grid-cols-4"
        @keydown="onKeydown"
    >
        <button
            v-for="type in finishTypes"
            :key="type.key"
            type="button"
            role="radio"
            :aria-checked="isSelected(type)"
            :tabindex="isSelected(type) ? 0 : -1"
            class="flex flex-col items-start gap-0.5 rounded-md border px-3 py-2.5 text-left transition-colors focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-ring"
            :class="
                isSelected(type)
                    ? 'border-foreground bg-accent'
                    : 'border-border hover:bg-accent/60'
            "
            @click="select(type)"
        >
            <span class="flex items-center gap-1.5 text-sm font-medium">
                <span class="size-1.5 rounded-full" :class="type.dot" />
                {{ type.label }}
            </span>
            <span class="font-mono text-xs text-muted-foreground"
                >+{{ type.points }}</span
            >
        </button>
    </div>
</template>
