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
import { computed, ref } from 'vue';
import FinishBadge from '@/components/FinishBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import ScoreEditDialog from '@/components/scoring/ScoreEditDialog.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { skeletonRows, useServerQuery } from '@/composables/useServerQuery';
import { formatDateTime } from '@/lib/activity';
import type { FinishKey } from '@/lib/finishTypes';
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

const { filters, loading, apply, debouncedApply } = useServerQuery(
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

const rows = computed(() =>
    loading.value
        ? skeletonRows(props.scores.data.length, props.scores.per_page)
        : props.scores.data,
);

const finishOptions: { label: string; value: FinishKey | null }[] = [
    { label: 'All finishes', value: null },
    { label: 'Spin', value: 'spin' },
    { label: 'Over', value: 'over' },
    { label: 'Burst', value: 'burst' },
    { label: 'Extreme', value: 'extreme' },
];

// One "Finish" filter in the UI, mapped onto the score / is_burst params.
const finishFilter = computed<FinishKey | null>({
    get() {
        if (filters.is_burst === '1') {
            return 'burst';
        }

        switch (filters.score) {
            case 1:
                return 'spin';
            case 2:
                return 'over';
            case 3:
                return 'extreme';
            default:
                return null;
        }
    },
    set(value) {
        const map: Record<
            FinishKey,
            { score: number | null; is_burst: string | null }
        > = {
            spin: { score: 1, is_burst: null },
            over: { score: 2, is_burst: '0' },
            burst: { score: null, is_burst: '1' },
            extreme: { score: 3, is_burst: null },
        };
        const next = value ? map[value] : { score: null, is_burst: null };

        filters.score = next.score;
        filters.is_burst = next.is_burst;
        filters.page = 1;
        apply();
    },
});

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

const hasFilters = computed(
    () => !!filters.search || finishFilter.value !== null,
);

const editDialogVisible = ref(false);
const editingScore = ref<Score | null>(null);

function openEdit(score: Score): void {
    editingScore.value = score;
    editDialogVisible.value = true;
}
</script>

<template>
    <Head title="Scores" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            title="Scores"
            description="Every recorded battle. Use this page to fix a mistake. Scores can't be deleted."
        />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <IconField class="w-full sm:max-w-xs">
                <InputIcon class="pi pi-search" />
                <InputText
                    v-model="filters.search"
                    placeholder="Search by player"
                    aria-label="Search scores by player"
                    fluid
                    @input="onSearch"
                />
            </IconField>

            <Select
                v-model="finishFilter"
                :options="finishOptions"
                option-label="label"
                option-value="value"
                aria-label="Filter by finish"
                class="w-full sm:w-44"
            />
        </div>

        <DataTable
            :value="rows"
            data-key="id"
            :aria-busy="loading"
            lazy
            paginator
            :rows="scores.per_page"
            :first="(scores.current_page - 1) * scores.per_page"
            :total-records="scores.total"
            :rows-per-page-options="[15, 30, 50]"
            :sort-field="filters.sort"
            :sort-order="filters.direction === 'asc' ? 1 : -1"
            row-hover
            class="overflow-hidden rounded-lg border border-border"
            @page="onPage"
            @sort="onSort"
        >
            <template #empty>
                <div class="py-10 text-center text-sm text-muted-foreground">
                    {{
                        hasFilters
                            ? 'No scores match these filters.'
                            : 'No scores recorded yet.'
                    }}
                </div>
            </template>

            <Column field="player_name" header="Player" sortable>
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-4 w-36" />
                    <span v-else class="font-medium">{{
                        data.player_name
                    }}</span>
                </template>
            </Column>
            <Column field="score" header="Finish" sortable>
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-5 w-20" />
                    <FinishBadge
                        v-else
                        :score="data.score"
                        :is-burst="data.is_burst"
                    />
                </template>
            </Column>
            <Column field="created_at" header="Recorded" sortable>
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-4 w-28" />
                    <span v-else class="text-muted-foreground">{{
                        formatDateTime(data.created_at)
                    }}</span>
                </template>
            </Column>
            <Column :style="{ width: '6rem' }">
                <template #body="{ data }">
                    <div v-if="loading" class="flex justify-end">
                        <Skeleton class="h-7 w-12" />
                    </div>
                    <div v-else class="flex justify-end">
                        <Button
                            label="Edit"
                            size="small"
                            severity="secondary"
                            text
                            :aria-label="`Edit score for ${data.player_name}`"
                            @click="openEdit(data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>
    </div>

    <ScoreEditDialog
        v-model:visible="editDialogVisible"
        :score="editingScore"
    />
</template>
