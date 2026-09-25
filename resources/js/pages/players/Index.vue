<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import type {
    DataTablePageEvent,
    DataTableSortEvent,
} from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Menu from 'primevue/menu';
import type { MenuItem } from 'primevue/menuitem';
import { useConfirm } from 'primevue/useconfirm';
import { computed, ref } from 'vue';
import PlayerController from '@/actions/App/Http/Controllers/PlayerController';
import PageHeader from '@/components/PageHeader.vue';
import PlayerFormDialog from '@/components/scoring/PlayerFormDialog.vue';
import ScoreEntriesDialog from '@/components/scoring/ScoreEntriesDialog.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { skeletonRows, useServerQuery } from '@/composables/useServerQuery';
import { index as playersIndex } from '@/routes/players';
import type { Paginated, Player } from '@/types/scoring';

const props = defineProps<{
    players: Paginated<Player>;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Players', href: playersIndex() }],
    },
});

const { filters, loading, apply, debouncedApply } = useServerQuery(
    playersIndex().url,
    {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        page: props.players.current_page,
    },
    { only: ['players', 'filters'] },
);

const rows = computed(() =>
    loading.value
        ? skeletonRows(props.players.data.length, props.players.per_page)
        : props.players.data,
);

function onPage(event: DataTablePageEvent): void {
    filters.page = event.page + 1;
    filters.per_page = event.rows;
    apply();
}

function onSort(event: DataTableSortEvent): void {
    filters.sort = (event.sortField as string) || 'name';
    filters.direction = event.sortOrder === 1 ? 'asc' : 'desc';
    filters.page = 1;
    apply();
}

function onSearch(): void {
    filters.page = 1;
    debouncedApply();
}

function clearSearch(): void {
    filters.search = '';
    filters.page = 1;
    apply();
}

const confirm = useConfirm();
const menu = ref<InstanceType<typeof Menu> | null>(null);
const selectedPlayer = ref<Player | null>(null);

const playerDialogVisible = ref(false);
const scoreDialogVisible = ref(false);
const editingPlayer = ref<Player | null>(null);

const menuItems = ref<MenuItem[]>([
    {
        label: 'Edit player',
        icon: 'pi pi-pencil',
        command: () => {
            editingPlayer.value = selectedPlayer.value;
            playerDialogVisible.value = true;
        },
    },
    { separator: true },
    {
        label: 'Delete player',
        icon: 'pi pi-trash',
        command: () => confirmDelete(),
    },
]);

function toggleMenu(event: MouseEvent, player: Player): void {
    selectedPlayer.value = player;
    menu.value?.toggle(event);
}

function openScores(player: Player): void {
    selectedPlayer.value = player;
    scoreDialogVisible.value = true;
}

function openCreateDialog(): void {
    editingPlayer.value = null;
    playerDialogVisible.value = true;
}

function confirmDelete(): void {
    const player = selectedPlayer.value;

    if (!player) {
        return;
    }

    confirm.require({
        header: `Delete ${player.name}?`,
        message:
            'Their recorded scores will be deleted too. This can’t be undone.',
        rejectProps: { label: 'Cancel', severity: 'secondary', text: true },
        acceptProps: { label: 'Delete player', severity: 'danger' },
        accept: () => {
            router.delete(PlayerController.destroy.url(player.id), {
                preserveScroll: true,
            });
        },
    });
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Players" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            title="Players"
            description="Everyone in the round robin. Record a player's battles from their row."
        >
            <template #actions>
                <Button
                    label="Add player"
                    icon="pi pi-plus"
                    size="small"
                    @click="openCreateDialog"
                />
            </template>
        </PageHeader>

        <IconField class="w-full sm:max-w-xs">
            <InputIcon class="pi pi-search" />
            <InputText
                v-model="filters.search"
                placeholder="Search by name"
                aria-label="Search players"
                fluid
                @input="onSearch"
            />
            <InputIcon
                v-if="filters.search"
                class="pi pi-times cursor-pointer"
                role="button"
                aria-label="Clear search"
                @click="clearSearch"
            />
        </IconField>

        <DataTable
            :value="rows"
            data-key="id"
            :aria-busy="loading"
            lazy
            paginator
            :rows="players.per_page"
            :first="(players.current_page - 1) * players.per_page"
            :total-records="players.total"
            :rows-per-page-options="[10, 25, 50]"
            :sort-field="filters.sort"
            :sort-order="filters.direction === 'asc' ? 1 : -1"
            row-hover
            class="overflow-hidden rounded-lg border border-border"
            @page="onPage"
            @sort="onSort"
        >
            <template #empty>
                <div class="py-10 text-center text-sm text-muted-foreground">
                    <template v-if="filters.search"
                        >No players match “{{ filters.search }}”.</template
                    >
                    <template v-else
                        >No players yet. Add the first one to start recording
                        scores.</template
                    >
                </div>
            </template>

            <Column field="name" header="Name" sortable>
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-4 w-36" />
                    <span v-else class="font-medium">{{ data.name }}</span>
                </template>
            </Column>
            <Column field="date_started" header="Started" sortable>
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-4 w-24" />
                    <span v-else class="text-muted-foreground">{{
                        formatDate(data.date_started)
                    }}</span>
                </template>
            </Column>
            <Column field="scores_count" header="Battles" sortable>
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-4 w-8" />
                    <span v-else class="font-mono">{{
                        data.scores_count
                    }}</span>
                </template>
            </Column>
            <Column field="scores_total" header="Points" sortable>
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-4 w-8" />
                    <span v-else class="font-mono font-medium">{{
                        data.scores_total
                    }}</span>
                </template>
            </Column>
            <Column :style="{ width: '9rem' }">
                <template #body="{ data }">
                    <div
                        v-if="loading"
                        class="flex items-center justify-end gap-2"
                    >
                        <Skeleton class="h-7 w-20" />
                        <Skeleton class="size-7" />
                    </div>
                    <div v-else class="flex items-center justify-end gap-1">
                        <Button
                            label="Record"
                            icon="pi pi-plus"
                            size="small"
                            severity="secondary"
                            outlined
                            :aria-label="`Record scores for ${data.name}`"
                            @click="openScores(data)"
                        />
                        <Button
                            icon="pi pi-ellipsis-h"
                            text
                            rounded
                            size="small"
                            severity="secondary"
                            :aria-label="`More actions for ${data.name}`"
                            @click="toggleMenu($event, data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>

    <Menu ref="menu" :model="menuItems" :popup="true" />
    <ConfirmDialog />

    <PlayerFormDialog
        v-model:visible="playerDialogVisible"
        :player="editingPlayer"
    />
    <ScoreEntriesDialog
        v-model:visible="scoreDialogVisible"
        :player="selectedPlayer"
    />
</template>
