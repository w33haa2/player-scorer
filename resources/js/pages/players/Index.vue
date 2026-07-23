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
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';
import PlayerController from '@/actions/App/Http/Controllers/PlayerController';
import PlayerFormDialog from '@/components/scoring/PlayerFormDialog.vue';
import ScoreEntriesDialog from '@/components/scoring/ScoreEntriesDialog.vue';
import { useServerQuery } from '@/composables/useServerQuery';
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

const { filters, apply, debouncedApply } = useServerQuery(
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
        label: 'Update player',
        icon: 'pi pi-pencil',
        command: () => {
            editingPlayer.value = selectedPlayer.value;
            playerDialogVisible.value = true;
        },
    },
    {
        label: 'Add score',
        icon: 'pi pi-plus-circle',
        command: () => {
            scoreDialogVisible.value = true;
        },
    },
    { separator: true },
    {
        label: 'Delete player',
        icon: 'pi pi-trash',
        class: 'text-red-500',
        command: () => confirmDelete(),
    },
]);

function toggleMenu(event: MouseEvent, player: Player): void {
    selectedPlayer.value = player;
    menu.value?.toggle(event);
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
        header: 'Delete player',
        message: `Delete ${player.name}? This also removes all of their scores.`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Cancel', severity: 'secondary', text: true },
        acceptProps: { label: 'Delete', severity: 'danger' },
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

    <div class="flex flex-col gap-4 p-4 sm:p-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-xl font-semibold">Players</h1>
                <p class="text-surface-500 dark:text-surface-400 text-sm">
                    Manage tournament players and record their scores.
                </p>
            </div>
            <Button
                label="Add Player"
                icon="pi pi-user-plus"
                class="w-full sm:w-auto"
                @click="openCreateDialog"
            />
        </div>

        <!-- Search -->
        <IconField class="w-full sm:max-w-xs">
            <InputIcon class="pi pi-search" />
            <InputText
                v-model="filters.search"
                placeholder="Search players by name…"
                class="w-full"
                fluid
                @input="onSearch"
            />
            <InputIcon
                v-if="filters.search"
                class="pi pi-times cursor-pointer"
                @click="clearSearch"
            />
        </IconField>

        <DataTable
            :value="players.data"
            data-key="id"
            lazy
            paginator
            :rows="players.per_page"
            :first="(players.current_page - 1) * players.per_page"
            :total-records="players.total"
            :rows-per-page-options="[10, 25, 50]"
            :sort-field="filters.sort"
            :sort-order="filters.direction === 'asc' ? 1 : -1"
            striped-rows
            class="border-surface-200 dark:border-surface-700 overflow-hidden rounded-lg border"
            @page="onPage"
            @sort="onSort"
        >
            <template #empty>
                <div class="text-surface-500 py-8 text-center">
                    {{
                        filters.search
                            ? 'No players match your search.'
                            : 'No players yet. Add your first player.'
                    }}
                </div>
            </template>

            <Column field="name" header="Name" sortable />
            <Column header="Date started" sortable field="date_started">
                <template #body="{ data }">{{
                    formatDate(data.date_started)
                }}</template>
            </Column>
            <Column field="scores_count" header="Entries" sortable>
                <template #body="{ data }">
                    <Tag :value="data.scores_count" severity="secondary" />
                </template>
            </Column>
            <Column field="scores_total" header="Total points" sortable>
                <template #body="{ data }">
                    <span class="font-semibold">{{ data.scores_total }}</span>
                </template>
            </Column>
            <Column
                header="Actions"
                :style="{ width: '5rem' }"
                :body-style="{ textAlign: 'center' }"
            >
                <template #body="{ data }">
                    <Button
                        icon="pi pi-ellipsis-v"
                        text
                        rounded
                        severity="secondary"
                        aria-label="Actions"
                        @click="toggleMenu($event, data)"
                    />
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
