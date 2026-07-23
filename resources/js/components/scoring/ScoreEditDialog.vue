<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import { watch } from 'vue';
import ScoreController from '@/actions/App/Http/Controllers/ScoreController';
import type { Score } from '@/types/scoring';

const props = defineProps<{
    score?: Score | null;
}>();

const visible = defineModel<boolean>('visible', { required: true });

const form = useForm({
    score: 1,
    is_burst: false,
});

watch(visible, (open) => {
    if (!open || !props.score) {
        return;
    }

    form.clearErrors();
    form.score = props.score.score;
    form.is_burst = props.score.is_burst;
});

// A burst finish always scores exactly 2, so lock the value.
function onBurstToggle(): void {
    if (form.is_burst) {
        form.score = 2;
    }
}

function submit(): void {
    if (!props.score) {
        return;
    }

    form.put(ScoreController.update.url(props.score.id), {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
        },
    });
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="`Update Score${score ? ' — ' + score.player_name : ''}`"
        :style="{ width: '95vw', maxWidth: '28rem' }"
        :draggable="false"
        dismissable-mask
    >
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium">Score</label>
                <InputNumber
                    v-model="form.score"
                    :min="1"
                    :max="3"
                    :disabled="form.is_burst"
                    show-buttons
                    button-layout="horizontal"
                    increment-button-icon="pi pi-plus"
                    decrement-button-icon="pi pi-minus"
                    :invalid="!!form.errors.score"
                    :input-style="{ width: '3rem' }"
                />
                <Message
                    v-if="form.errors.score"
                    severity="error"
                    variant="simple"
                    size="small"
                >
                    {{ form.errors.score }}
                </Message>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox
                    input-id="edit-burst"
                    v-model="form.is_burst"
                    binary
                    @change="onBurstToggle"
                />
                <label for="edit-burst" class="text-sm"
                    >Is it burst finish?</label
                >
            </div>

            <div class="mt-2 flex justify-end gap-2">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="visible = false"
                />
                <Button type="submit" label="Save" :loading="form.processing" />
            </div>
        </form>
    </Dialog>
</template>
