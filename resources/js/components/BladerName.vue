<script setup lang="ts">
import type { TeamSummary } from '@/types/scoring';

/**
 * A blader name with the team's jersey tag in front, e.g. "DNV Ferrari_430".
 *
 * Renders no surrounding whitespace because it sits mid-sentence in the
 * activity feed. The tag is sized in em so it scales with headings and rows.
 */
defineProps<{
    name: string;
    team?: TeamSummary | null;
}>();
</script>

<template>
    <span
        ><template v-if="team?.acronym"
            ><span
                class="rounded-[0.3em] bg-team/10 px-[0.35em] py-[0.1em] font-mono text-[0.75em] font-semibold tracking-wide text-team"
                >{{ team.acronym }}</span
            >{{ ' ' }}</template
        >{{ name }}</span
    >
</template>
