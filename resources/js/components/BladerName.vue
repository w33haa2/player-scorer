<script setup lang="ts">
import FlameText from '@/components/FlameText.vue';
import type { TeamSummary } from '@/types/scoring';

/**
 * A blader name with the team's jersey tag in front, e.g. "DNV Ferrari_430".
 *
 * Renders no surrounding whitespace because it sits mid-sentence in the
 * activity feed. The tag is sized in em so it scales with headings and rows.
 * `flame` sets just the name (not the tag) on fire.
 */
defineProps<{
    name: string;
    team?: TeamSummary | null;
    flame?: boolean;
}>();
</script>

<template>
    <span
        ><template v-if="team?.acronym"
            ><span
                class="rounded-[0.3em] bg-team/10 px-[0.35em] py-[0.1em] font-mono text-[0.75em] font-semibold tracking-wide text-team"
                >{{ team.acronym }}</span
            >{{ ' ' }}</template
        ><FlameText v-if="flame" :text="name" /><template v-else>{{
            name
        }}</template></span
    >
</template>
