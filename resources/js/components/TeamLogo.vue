<script setup lang="ts">
import type { TeamSummary } from '@/types/scoring';

const props = withDefaults(
    defineProps<{
        team: TeamSummary | null | undefined;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    { size: 'sm' },
);

const sizes = {
    sm: 'size-5',
    md: 'size-6',
    lg: 'size-10',
} as const;
</script>

<template>
    <img
        v-if="props.team?.logo_url"
        v-tooltip.top="props.team.name"
        :src="props.team.logo_url"
        :alt="props.team.name"
        width="128"
        height="128"
        loading="lazy"
        decoding="async"
        class="shrink-0 rounded-sm object-contain"
        :class="sizes[props.size]"
    />
    <!-- Keeps names aligned in lists when a player has no team. -->
    <span
        v-else
        class="shrink-0"
        :class="sizes[props.size]"
        aria-hidden="true"
    />
</template>
