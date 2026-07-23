<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import { computed, watch } from 'vue';
import ScoreController from '@/actions/App/Http/Controllers/ScoreController';
import type { Player, ScoreEntry } from '@/types/scoring';

const props = defineProps<{
    player?: Player | null;
}>();

const visible = defineModel<boolean>('visible', { required: true });

type FinishType = {
    key: string;
    label: string;
    points: number;
    icon: string;
    score: number;
    is_burst: boolean;
    /** Classes for the selected button (border + ring + tint). */
    ring: string;
    /** Classes for the selected icon badge. */
    badge: string;
    /** Text accent when selected. */
    text: string;
    /** Chip classes for the header summary tag. */
    tag: string;
};

// The four Beyblade X finish types, mapped to (score, is_burst) + accent colors.
const finishTypes: FinishType[] = [
    {
        key: 'spin',
        label: 'Spin',
        points: 1,
        icon: 'pi pi-sync',
        score: 1,
        is_burst: false,
        ring: 'border-sky-500 bg-sky-500/5 ring-1 ring-sky-500',
        badge: 'bg-sky-500 text-white',
        text: 'text-sky-600 dark:text-sky-400',
        tag: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
    },
    {
        key: 'over',
        label: 'Over',
        points: 2,
        icon: 'pi pi-arrow-up',
        score: 2,
        is_burst: false,
        ring: 'border-violet-500 bg-violet-500/5 ring-1 ring-violet-500',
        badge: 'bg-violet-500 text-white',
        text: 'text-violet-600 dark:text-violet-400',
        tag: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    },
    {
        key: 'burst',
        label: 'Burst',
        points: 2,
        icon: 'pi pi-bolt',
        score: 2,
        is_burst: true,
        ring: 'border-orange-500 bg-orange-500/5 ring-1 ring-orange-500',
        badge: 'bg-orange-500 text-white',
        text: 'text-orange-600 dark:text-orange-400',
        tag: 'bg-orange-500/10 text-orange-600 dark:text-orange-400',
    },
    {
        key: 'extreme',
        label: 'Extreme',
        points: 3,
        icon: 'pi pi-star-fill',
        score: 3,
        is_burst: false,
        ring: 'border-rose-500 bg-rose-500/5 ring-1 ring-rose-500',
        badge: 'bg-rose-500 text-white',
        text: 'text-rose-600 dark:text-rose-400',
        tag: 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
    },
];

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

function keyFor(entry: ScoreEntry): string {
    if (entry.is_burst) {
        return 'burst';
    }

    if (entry.score === 1) {
        return 'spin';
    }

    return entry.score === 3 ? 'extreme' : 'over';
}

function typeFor(entry: ScoreEntry): FinishType {
    return (
        finishTypes.find((type) => type.key === keyFor(entry)) ?? finishTypes[0]
    );
}

function selectFinish(entry: ScoreEntry, type: FinishType): void {
    entry.score = type.score;
    entry.is_burst = type.is_burst;
}

// Arrow-key navigation within a battle's radio group.
function onFinishKeydown(entry: ScoreEntry, event: KeyboardEvent): void {
    const keys = ['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'];

    if (!keys.includes(event.key)) {
        return;
    }

    event.preventDefault();

    const current = finishTypes.findIndex((type) => type.key === keyFor(entry));
    const forward = event.key === 'ArrowRight' || event.key === 'ArrowDown';
    const next =
        (current + (forward ? 1 : -1) + finishTypes.length) %
        finishTypes.length;

    selectFinish(entry, finishTypes[next]);

    const container = event.currentTarget as HTMLElement;
    const radios =
        container.querySelectorAll<HTMLButtonElement>('[role="radio"]');
    radios[next]?.focus();
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
        :header="`Add Scores${player ? ' — ' + player.name : ''}`"
        :style="{ width: '95vw', maxWidth: '36rem' }"
        :draggable="false"
        dismissable-mask
    >
        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <p class="text-surface-500 dark:text-surface-400 text-sm">
                Pick the finish type for each battle. Add as many as you need.
            </p>

            <TransitionGroup
                tag="div"
                name="battle"
                class="-mr-2 flex max-h-[52vh] flex-col gap-4 overflow-y-auto pr-2"
            >
                <div
                    v-for="(entry, index) in form.scores"
                    :key="entry._key"
                    class="border-surface-200 dark:border-surface-700 rounded-2xl border p-4 sm:p-5"
                >
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="text-surface-500 dark:text-surface-400 text-xs font-semibold tracking-wide uppercase"
                            >
                                Battle {{ index + 1 }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="typeFor(entry).tag"
                            >
                                <span :class="typeFor(entry).icon" />
                                {{ typeFor(entry).label }} ·
                                {{ typeFor(entry).points }} pt
                            </span>
                        </div>
                        <div class="flex items-center">
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
                                severity="danger"
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

                    <div
                        role="radiogroup"
                        :aria-label="`Finish type for battle ${index + 1}`"
                        class="grid grid-cols-2 gap-3"
                        @keydown="onFinishKeydown(entry, $event)"
                    >
                        <button
                            v-for="type in finishTypes"
                            :key="type.key"
                            type="button"
                            role="radio"
                            :aria-checked="keyFor(entry) === type.key"
                            :tabindex="keyFor(entry) === type.key ? 0 : -1"
                            class="flex items-center gap-3 rounded-xl border p-3 text-left transition duration-200 select-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50"
                            :class="
                                keyFor(entry) === type.key
                                    ? type.ring
                                    : 'border-surface-200 dark:border-surface-700 hover:border-surface-300 dark:hover:border-surface-600'
                            "
                            @click="selectFinish(entry, type)"
                        >
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg transition duration-200"
                                :class="
                                    keyFor(entry) === type.key
                                        ? type.badge
                                        : 'bg-surface-100 text-surface-500 dark:bg-surface-800 dark:text-surface-300'
                                "
                            >
                                <span :class="[type.icon, 'text-lg']" />
                            </span>
                            <span class="flex flex-col">
                                <span
                                    class="leading-tight font-medium"
                                    :class="
                                        keyFor(entry) === type.key
                                            ? type.text
                                            : ''
                                    "
                                    >{{ type.label }}</span
                                >
                                <span
                                    class="text-surface-500 dark:text-surface-400 text-xs"
                                    >{{ type.points }} pt</span
                                >
                            </span>
                        </button>
                    </div>

                    <Message
                        v-if="errorFor(index)"
                        severity="error"
                        variant="simple"
                        size="small"
                        class="mt-2"
                    >
                        {{ errorFor(index) }}
                    </Message>
                </div>
            </TransitionGroup>

            <Button
                type="button"
                label="Add another battle"
                icon="pi pi-plus"
                severity="secondary"
                outlined
                class="w-full border-dashed py-3"
                @click="addEntry"
            />

            <div
                class="border-surface-200 dark:border-surface-700 flex flex-col gap-3 border-t pt-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <span class="text-surface-500 dark:text-surface-400 text-sm">
                    {{ form.scores.length }}
                    {{ form.scores.length === 1 ? 'battle' : 'battles' }} ·
                    <span
                        class="text-surface-700 dark:text-surface-200 font-semibold"
                        >{{ totalPoints }} pts</span
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
                        :label="`Save ${form.scores.length > 1 ? form.scores.length + ' scores' : 'score'}`"
                        icon="pi pi-check"
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
        opacity 0.25s ease,
        transform 0.25s ease;
}

.battle-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}

.battle-leave-to {
    opacity: 0;
    transform: translateX(12px);
}
</style>
