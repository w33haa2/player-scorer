<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import type { DataTablePageEvent } from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { computed } from 'vue';
import FinishBadge from '@/components/FinishBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { skeletonRows, useServerQuery } from '@/composables/useServerQuery';
import { describeChange, formatDateTime, timeAgo } from '@/lib/activity';
import { index as auditLogs } from '@/routes/audit-logs';
import type { AuditLog, Paginated } from '@/types/scoring';

const props = defineProps<{
    logs: Paginated<AuditLog>;
    filters: {
        search: string;
        action: string | null;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Audit log', href: auditLogs() }],
    },
});

const actionOptions = [
    { label: 'All changes', value: null },
    { label: 'Added', value: 'created' },
    { label: 'Edited', value: 'updated' },
    { label: 'Removed', value: 'deleted' },
];

const { filters, loading, apply, debouncedApply } = useServerQuery(
    auditLogs().url,
    {
        search: props.filters.search,
        action: props.filters.action,
        per_page: props.filters.per_page,
        page: props.logs.current_page,
    },
    { only: ['logs', 'filters'] },
);

const rows = computed(() =>
    loading.value
        ? skeletonRows(props.logs.data.length, props.logs.per_page)
        : props.logs.data,
);

function onPage(event: DataTablePageEvent): void {
    filters.page = event.page + 1;
    filters.per_page = event.rows;
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

const hasFilters = computed(() => !!filters.search || !!filters.action);
</script>

<template>
    <Head title="Audit log" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <PageHeader
            title="Audit log"
            description="Every score that was added or changed, and who did it."
        />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <IconField class="w-full sm:max-w-xs">
                <InputIcon class="pi pi-search" />
                <InputText
                    v-model="filters.search"
                    placeholder="Search by player or admin"
                    aria-label="Search the audit log"
                    fluid
                    @input="onSearch"
                />
            </IconField>

            <Select
                v-model="filters.action"
                :options="actionOptions"
                option-label="label"
                option-value="value"
                aria-label="Filter by change type"
                class="w-full sm:w-44"
                @change="onFilterChange"
            />
        </div>

        <DataTable
            :value="rows"
            data-key="id"
            :aria-busy="loading"
            lazy
            paginator
            :rows="logs.per_page"
            :first="(logs.current_page - 1) * logs.per_page"
            :total-records="logs.total"
            :rows-per-page-options="[20, 50, 100]"
            row-hover
            class="overflow-hidden rounded-lg border border-border"
            @page="onPage"
        >
            <template #empty>
                <div class="py-10 text-center text-sm text-muted-foreground">
                    {{
                        hasFilters
                            ? 'No changes match these filters.'
                            : 'No changes recorded yet.'
                    }}
                </div>
            </template>

            <Column header="Change">
                <template #body="{ data }">
                    <div v-if="loading" class="flex flex-col gap-2 py-0.5">
                        <Skeleton class="h-4 w-64 max-w-full" />
                        <Skeleton class="h-5 w-20" />
                    </div>
                    <div v-else class="flex flex-col gap-1.5 py-0.5">
                        <span>{{ describeChange(data.changes) }}</span>
                        <FinishBadge
                            v-if="data.changes.new"
                            class="self-start"
                            :score="data.changes.new.score"
                            :is-burst="data.changes.new.is_burst"
                        />
                    </div>
                </template>
            </Column>
            <Column field="user_name" header="By" :style="{ width: '10rem' }">
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-4 w-20" />
                    <span v-else class="text-muted-foreground">{{
                        data.user_name
                    }}</span>
                </template>
            </Column>
            <Column header="When" :style="{ width: '9rem' }">
                <template #body="{ data }">
                    <Skeleton v-if="loading" class="h-4 w-16" />
                    <time
                        v-else
                        class="whitespace-nowrap text-muted-foreground"
                        :datetime="data.created_at ?? undefined"
                        :title="formatDateTime(data.created_at)"
                        >{{ timeAgo(data.created_at) }}</time
                    >
                </template>
            </Column>
        </DataTable>
    </div>
</template>
