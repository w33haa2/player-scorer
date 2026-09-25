<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import { computed, watch } from 'vue';
import ScoreController from '@/actions/App/Http/Controllers/ScoreController';
import FinishTypePicker from '@/components/scoring/FinishTypePicker.vue';
import type { Player, ScoreEntry } from '@/types/scoring';

const props = defineProps<{
    player?: Player | null;
}>();

const visible = defineModel<boolean>('visible', { required: true });

type FormEntry = ScoreEntry & { _key: number };

let keyCounter = 0;

function makeEntry(base?: ScoreEntry): FormEntry {
    return {
        _key: keyCounter++,
        score: base?.score ?? 1,
        is_burst: base?.is_burst ?? false,
    };
}

const form = useForm<{ scores: FormEntry[] }>({
    scores: [makeEntry()],
});

const totalPoints = computed(() =>
    form.scores.reduce((sum, entry) => sum + entry.score, 0),
);

function resetEntries(): void {
    form.clearErrors();
    form.scores = [makeEntry()];
}

watch(visible, (open) => {
    if (open) {
        resetEntries();
    }
});

function addEntry(): void {
    form.scores.push(makeEntry());
}

function duplicateEntry(index: number): void {
    form.scores.splice(index + 1, 0, makeEntry(form.scores[index]));
}

function removeEntry(index: number): void {
    if (form.scores.length > 1) {
        form.scores.splice(index, 1);
    }
}

function errorFor(index: number): string | undefined {
    const errors = form.errors as Record<string, string>;

    return (
        errors[`scores.${index}.score`] ?? errors[`scores.${index}.is_burst`]
    );
}

function submit(): void {
    if (!props.player) {
        return;
    }

    form.transform((data) => ({
        scores: data.scores.map((entry) => ({
            score: entry.score,
            is_burst: entry.is_burst,
        })),
    })).post(ScoreController.store.url(props.player.id), {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            resetEntries();
        },
    });
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="
            player ? `Record scores for ${player.blader_name}` : 'Record scores'
        "
        :style="{ width: '95vw', maxWidth: '36rem' }"
        :draggable="false"
        dismissable-mask
    >
        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <p class="text-sm text-muted-foreground">
                Choose how each battle ended. Add a row per battle.
            </p>

            <TransitionGroup
                tag="ol"
                name="battle"
                class="-mr-2 flex max-h-[52vh] flex-col gap-3 overflow-y-auto pr-2"
            >
                <li
                    v-for="(entry, index) in form.scores"
                    :key="entry._key"
                    class="rounded-lg border border-border p-3 sm:p-4"
                >
                    <div class="mb-2.5 flex items-center justify-between">
                        <span class="text-sm font-medium"
                            >Battle {{ index + 1 }}</span
                        >
                        <div class="-mr-1.5 flex items-center">
                            <Button
                                type="button"
                                icon="pi pi-copy"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                aria-label="Duplicate battle"
                                v-tooltip.top="'Duplicate'"
                                @click="duplicateEntry(index)"
                            />
                            <Button
                                type="button"
                                icon="pi pi-times"
                                severity="secondary"
                                text
                                rounded
                                size="small"
                                :disabled="form.scores.length === 1"
                                aria-label="Remove battle"
                                v-tooltip.top="'Remove'"
                                @click="removeEntry(index)"
                            />
                        </div>
                    </div>

                    <FinishTypePicker
                        v-model:score="entry.score"
                        v-model:is-burst="entry.is_burst"
                        :label="`How battle ${index + 1} ended`"
                    />

                    <Message
                        v-if="errorFor(index)"
                        severity="error"
                        variant="simple"
                        size="small"
                        class="mt-2"
                    >
                        {{ errorFor(index) }}
                    </Message>
                </li>
            </TransitionGroup>

            <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-border py-2.5 text-sm text-muted-foreground transition-colors hover:border-ring hover:text-foreground"
                @click="addEntry"
            >
                <span class="pi pi-plus text-xs" />
                Add battle
            </button>

            <div
                class="flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <span class="text-sm text-muted-foreground">
                    {{ form.scores.length }}
                    {{ form.scores.length === 1 ? 'battle' : 'battles' }},
                    <span class="font-mono text-foreground"
                        >{{ totalPoints }}
                        {{ totalPoints === 1 ? 'point' : 'points' }}</span
                    >
                </span>

                <div class="flex gap-2">
                    <Button
                        type="button"
                        label="Cancel"
                        severity="secondary"
                        text
                        class="flex-1 sm:flex-none"
                        @click="visible = false"
                    />
                    <Button
                        type="submit"
                        label="Save"
                        class="flex-1 sm:flex-none"
                        :loading="form.processing"
                    />
                </div>
            </div>
        </form>
    </Dialog>
</template>

<style scoped>
.battle-enter-active,
.battle-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.battle-enter-from {
    opacity: 0;
    transform: translateY(-4px);
}

.battle-leave-to {
    opacity: 0;
}
</style>
