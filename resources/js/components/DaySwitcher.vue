<script setup lang="ts">
import { computed } from 'vue';
import { formatDayDate } from '@/lib/tournamentDays';
import type { TournamentDayOption } from '@/types/scoring';

const props = defineProps<{
    days: TournamentDayOption[];
    /** Selected day number, or null for the whole tournament. */
    selected: number | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{ select: [day: number | null] }>();

const options = computed(() => [
    { number: null, label: 'Overall', title: 'Whole tournament', live: false },
    ...props.days.map((day) => ({
        number: day.number as number | null,
        label: day.label,
        title: formatDayDate(day.date),
        live: day.is_today,
    })),
]);

// New scores always land on the latest day, so that's what is aggregating.
const latestDay = computed(() => props.days.at(-1) ?? null);

function select(day: number | null): void {
    if (!props.disabled && day !== props.selected) {
        emit('select', day);
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        <div
            role="group"
            aria-label="Aggregate by tournament day"
            class="inline-flex rounded-lg border border-border bg-card p-0.5"
        >
            <button
                v-for="option in options"
                :key="option.label"
                type="button"
                :title="option.title"
                :aria-pressed="option.number === selected"
                :disabled="disabled"
                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-ring disabled:cursor-wait"
                :class="
                    option.number === selected
                        ? 'bg-accent font-medium text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="select(option.number)"
            >
                {{ option.label }}
                <span
                    v-if="option.live"
                    class="size-1.5 rounded-full bg-emerald-500"
                    aria-hidden="true"
                />
            </button>
        </div>

        <p
            v-if="latestDay"
            class="flex items-center gap-2 text-xs text-muted-foreground"
        >
            <span
                v-if="latestDay.is_today"
                class="relative flex size-2"
                aria-hidden="true"
            >
                <span
                    class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-500 opacity-60 motion-reduce:animate-none"
                />
                <span
                    class="relative inline-flex size-2 rounded-full bg-emerald-500"
                />
            </span>
            <template v-if="latestDay.is_today">
                <span class="font-medium text-foreground">Live</span>
                Now aggregating {{ latestDay.label }} ·
                {{ formatDayDate(latestDay.date) }}
            </template>
            <template v-else>
                Latest: {{ latestDay.label }} ·
                {{ formatDayDate(latestDay.date) }}
            </template>
        </p>
    </div>
</template>
