<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import { watch } from 'vue';
import ScoreController from '@/actions/App/Http/Controllers/ScoreController';
import FinishTypePicker from '@/components/scoring/FinishTypePicker.vue';
import { formatDateTime } from '@/lib/activity';
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
        header="Correct score"
        :style="{ width: '95vw', maxWidth: '32rem' }"
        :draggable="false"
        dismissable-mask
    >
        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <p v-if="score" class="text-sm text-muted-foreground">
                {{ score.player_name }} · recorded
                {{ formatDateTime(score.created_at) }}
            </p>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">Finish</span>
                <FinishTypePicker
                    v-model:score="form.score"
                    v-model:is-burst="form.is_burst"
                    label="How the battle ended"
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

            <div class="flex justify-end gap-2 border-t border-border pt-4">
                <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="visible = false"
                />
                <Button
                    type="submit"
                    label="Save changes"
                    :loading="form.processing"
                />
            </div>
        </form>
    </Dialog>
</template>
