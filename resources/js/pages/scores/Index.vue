<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import type {
    DataTablePageEvent,
    DataTableSortEvent,
} from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { ref } from 'vue';
import ScoreEditDialog from '@/components/scoring/ScoreEditDialog.vue';
import { useServerQuery } from '@/composables/useServerQuery';
import { index as scoresIndex } from '@/routes/scores';
import type { Paginated, Score } from '@/types/scoring';

const props = defineProps<{
    scores: Paginated<Score>;
    filters: {
        search: string;
        score: number | null;
        is_burst: string | null;
        sort: string;
        direction: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Scores', href: scoresIndex() }],
    },
});

const scoreOptions = [
    { label: 'All scores', value: null },
    { label: '1 · Spin finish', value: 1 },
    { label: '2 · Over / Burst finish', value: 2 },
    { label: '3 · Extreme finish', value: 3 },
];

const burstOptions = [
    { label: 'Any finish', value: null },
    { label: 'Burst only', value: '1' },
    { label: 'Non-burst', value: '0' },
];

const { filters, apply, debouncedApply } = useServerQuery(
    scoresIndex().url,
    {
        search: props.filters.search,
        score: props.filters.score,
        is_burst: props.filters.is_burst,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: props.filters.per_page,
        page: props.scores.current_page,
    },
    { only: ['scores', 'filters'] },
);

function onPage(event: DataTablePageEvent): void {
    filters.page = event.page + 1;
    filters.per_page = event.rows;
    apply();
}

function onSort(event: DataTableSortEvent): void {
    filters.sort = (event.sortField as string) || 'created_at';
    filters.direction = event.sortOrder === 1 ? 'asc' : 'desc';
    filters.page = 1;
    apply();
}

function onSearch(): void {
    filters.page = 1;
    debouncedApply();
}

function onFilterChange(): void {
    filters.page = 1;
    apply();
}

const editDialogVisible = ref(false);
const editingScore = ref<Score | null>(null);

function openEdit(score: Score): void {
    editingScore.value = score;
    editDialogVisible.value = true;
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head title="Scores" />

    <div class="flex flex-col gap-4 p-4 sm:p-6">
        <div>
            <h1 class="text-xl font-semibold">Scores</h1>
            <p class="text-surface-500 dark:text-surface-400 text-sm">
                Correct any mistyped scores. Entries cannot be deleted here.
            </p>
        </div>

        <!-- Filters -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
        >
            <IconField class="w-full sm:max-w-xs">
                <InputIcon class="pi pi-search" />
                <InputText
                    v-model="filters.search"
                    placeholder="Search by player…"
                    class="w-full"
                    fluid
                    @input="onSearch"
                />
            </IconField>

            <Select
                v-model="filters.score"
                :options="scoreOptions"
                option-label="label"
                option-value="value"
                placeholder="Score"
                class="w-full sm:w-52"
                @change="onFilterChange"
            />

            <Select
                v-model="filters.is_burst"
                :options="burstOptions"
                option-label="label"
                option-value="value"
                placeholder="Burst"
                class="w-full sm:w-44"
                @change="onFilterChange"
            />
        </div>

        <DataTable
            :value="scores.data"
            data-key="id"
            lazy
            paginator
            :rows="scores.per_page"
            :first="(scores.current_page - 1) * scores.per_page"
            :total-records="scores.total"
            :rows-per-page-options="[15, 30, 50]"
            :sort-field="filters.sort"
            :sort-order="filters.direction === 'asc' ? 1 : -1"
            striped-rows
            class="border-surface-200 dark:border-surface-700 overflow-hidden rounded-lg border"
            @page="onPage"
            @sort="onSort"
        >
            <template #empty>
                <div class="text-surface-500 py-8 text-center">
                    No scores match your filters.
                </div>
            </template>

            <Column field="player_name" header="Player" sortable />
            <Column field="score" header="Score" sortable>
                <template #body="{ data }">
                    <span class="font-semibold">{{ data.score }}</span>
                </template>
            </Column>
            <Column field="is_burst" header="Burst finish" sortable>
                <template #body="{ data }">
                    <Tag
                        v-if="data.is_burst"
                        value="Burst"
                        severity="warn"
                        icon="pi pi-bolt"
                    />
                    <span v-else class="text-surface-400">—</span>
                </template>
            </Column>
            <Column header="Recorded" field="created_at" sortable>
                <template #body="{ data }">{{
                    formatDateTime(data.created_at)
                }}</template>
            </Column>
            <Column
                header="Actions"
                :style="{ width: '5rem' }"
                :body-style="{ textAlign: 'center' }"
            >
                <template #body="{ data }">
                    <Button
                        icon="pi pi-pencil"
                        text
                        rounded
                        severity="secondary"
                        aria-label="Update score"
                        @click="openEdit(data)"
                    />
                </template>
            </Column>
        </DataTable>
    </div>

    <ScoreEditDialog
        v-model:visible="editDialogVisible"
        :score="editingScore"
    />
</template>
