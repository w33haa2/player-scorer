<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { CircleCheck } from '@lucide/vue';
import { computed, ref, useTemplateRef } from 'vue';
import PlayerDataController from '@/actions/App/Http/Controllers/Settings/PlayerDataController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/activity';
import type { PlayerDataStatus } from '@/types/scoring';

const props = defineProps<{
    playerData: PlayerDataStatus;
}>();

const open = ref(false);
const passwordInput = useTemplateRef('passwordInput');

const isSeeded = computed(() => props.playerData.seeded_at !== null);
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Player data"
            :description="`Load the DBBL roster: ${playerData.players} bladers across ${playerData.teams} teams, with their team logos.`"
        />

        <div
            v-if="isSeeded"
            class="flex items-start gap-3 rounded-lg border border-border p-4 text-sm"
        >
            <CircleCheck class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
            <p>
                Seeded {{ formatDateTime(playerData.seeded_at) }}
                <template v-if="playerData.seeded_by">
                    by
                    <span class="font-medium">{{
                        playerData.seeded_by
                    }}</span></template
                >. This can only be done once.
            </p>
        </div>

        <div v-else class="space-y-4 rounded-lg border border-border p-4">
            <div class="space-y-0.5">
                <p class="text-sm font-medium">
                    Replaces the current player list
                </p>
                <p class="text-sm text-muted-foreground">
                    Every current player, their recorded battles and the audit
                    log are deleted first. This can only be done once.
                </p>
            </div>

            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button
                        variant="outline"
                        data-test="seed-player-data-button"
                        >Seed player data</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="PlayerDataController.store.form()"
                        reset-on-success
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                        @success="open = false"
                        @error="() => passwordInput?.focus()"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle
                                >Replace all players with the DBBL
                                roster?</DialogTitle
                            >
                            <DialogDescription>
                                This permanently deletes every current player,
                                their recorded battles and the audit log, then
                                adds {{ playerData.players }} bladers across
                                {{ playerData.teams }} teams. It can only be
                                done once. Enter your password to confirm.
                            </DialogDescription>
                        </DialogHeader>

                        <InputError :message="errors.seed" />

                        <div class="grid gap-2">
                            <Label for="seed-password" class="sr-only"
                                >Password</Label
                            >
                            <PasswordInput
                                id="seed-password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Password"
                                autocomplete="current-password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-seed-player-data-button"
                            >
                                Seed player data
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
