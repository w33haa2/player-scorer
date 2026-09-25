<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { ref, watch } from 'vue';
import PlayerController from '@/actions/App/Http/Controllers/PlayerController';
import type { Player } from '@/types/scoring';

const props = defineProps<{
    player?: Player | null;
}>();

const visible = defineModel<boolean>('visible', { required: true });

const dateStarted = ref<Date | null>(null);

const form = useForm({
    name: '',
    date_started: null as string | null,
});

function toDateString(value: Date | null): string | null {
    if (!value) {
        return null;
    }

    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

// Sync the form whenever the dialog is opened for a new or existing player.
watch(visible, (open) => {
    if (!open) {
        return;
    }

    form.clearErrors();
    form.name = props.player?.name ?? '';
    dateStarted.value = props.player?.date_started
        ? new Date(props.player.date_started)
        : null;
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        date_started: toDateString(dateStarted.value),
    }));

    const onSuccess = () => {
        visible.value = false;
        form.reset();
        dateStarted.value = null;
    };

    if (props.player) {
        form.put(PlayerController.update.url(props.player.id), {
            onSuccess,
            preserveScroll: true,
        });
    } else {
        form.post(PlayerController.store.url(), {
            onSuccess,
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="player ? 'Edit player' : 'Add player'"
        :style="{ width: '95vw', maxWidth: '28rem' }"
        :draggable="false"
        dismissable-mask
    >
        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <div class="flex flex-col gap-2">
                <label for="player-name" class="text-sm font-medium"
                    >Name</label
                >
                <InputText
                    id="player-name"
                    v-model="form.name"
                    autofocus
                    fluid
                    :invalid="!!form.errors.name"
                />
                <Message
                    v-if="form.errors.name"
                    severity="error"
                    variant="simple"
                    size="small"
                >
                    {{ form.errors.name }}
                </Message>
            </div>

            <div class="flex flex-col gap-2">
                <label for="player-date" class="text-sm font-medium"
                    >Date started
                    <span class="font-normal text-muted-foreground"
                        >(optional)</span
                    ></label
                >
                <DatePicker
                    id="player-date"
                    v-model="dateStarted"
                    date-format="yy-mm-dd"
                    show-icon
                    fluid
                    :invalid="!!form.errors.date_started"
                />
                <p class="text-xs text-muted-foreground">
                    Used to decide Rookie of the Season.
                </p>
                <Message
                    v-if="form.errors.date_started"
                    severity="error"
                    variant="simple"
                    size="small"
                >
                    {{ form.errors.date_started }}
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
                    :label="player ? 'Save changes' : 'Add player'"
                    :loading="form.processing"
                />
            </div>
        </form>
    </Dialog>
</template>
