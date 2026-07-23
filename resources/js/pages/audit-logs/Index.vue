<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import type { DataTablePageEvent } from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useServerQuery } from '@/composables/useServerQuery';
import { index as auditLogs } from '@/routes/audit-logs';
import type { AuditLog, AuditLogChange, Paginated } from '@/types/scoring';

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
        breadcrumbs: [{ title: 'Audit Logs', href: auditLogs() }],
    },
});

type Severity = 'success' | 'info' | 'danger';

const actionSeverity: Record<AuditLogChange['action'], Severity> = {
    created: 'success',
    updated: 'info',
    deleted: 'danger',
};

const actionOptions = [
    { label: 'All actions', value: null },
    { label: 'Created', value: 'created' },
    { label: 'Updated', value: 'updated' },
    { label: 'Deleted', value: 'deleted' },
];

const { filters, apply, debouncedApply } = useServerQuery(
    auditLogs().url,
    {
        search: props.filters.search,
        action: props.filters.action,
        per_page: props.filters.per_page,
        page: props.logs.current_page,
    },
    { only: ['logs', 'filters'] },
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

function severityFor(action: AuditLogChange['action']): Severity {
    return actionSeverity[action] ?? 'info';
}

function describeScore(
    entry: { score: number; is_burst: boolean } | null,
): string {
    if (!entry) {
        return 'n/a';
    }

    return entry.is_burst ? `${entry.score} (burst)` : `${entry.score}`;
}

function describe(change: AuditLogChange): string {
    const player = change.player_name ?? `player #${change.player_id}`;

    switch (change.action) {
        case 'created':
            return `Added a score of ${describeScore(change.new)} for ${player}.`;
        case 'updated':
            return `Updated ${player}'s score from ${describeScore(change.old)} to ${describeScore(change.new)}.`;
        case 'deleted':
            return `Removed a score of ${describeScore(change.old)} for ${player}.`;
        default:
            return `Modified a score for ${player}.`;
    }
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
    <Head title="Audit Logs" />

    <div class="flex flex-col gap-4 p-4 sm:p-6">
        <div>
            <h1 class="text-xl font-semibold">Audit Logs</h1>
            <p class="text-surface-500 dark:text-surface-400 text-sm">
                Recent score changes made by the tournament admins.
            </p>
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <IconField class="w-full sm:max-w-xs">
                <InputIcon class="pi pi-search" />
                <InputText
                    v-model="filters.search"
                    placeholder="Search by player or admin…"
                    class="w-full"
                    fluid
                    @input="onSearch"
                />
            </IconField>

            <Select
                v-model="filters.action"
                :options="actionOptions"
                option-label="label"
                option-value="value"
                placeholder="Action"
                class="w-full sm:w-48"
                @change="onFilterChange"
            />
        </div>

        <DataTable
            :value="logs.data"
            data-key="id"
            lazy
            paginator
            :rows="logs.per_page"
            :first="(logs.current_page - 1) * logs.per_page"
            :total-records="logs.total"
            :rows-per-page-options="[20, 50, 100]"
            striped-rows
            class="border-surface-200 dark:border-surface-700 overflow-hidden rounded-lg border"
            @page="onPage"
        >
            <template #empty>
                <div class="text-surface-500 py-8 text-center">
                    No activity matches your filters.
                </div>
            </template>

            <Column header="When" :style="{ width: '11rem' }">
                <template #body="{ data }">{{
                    formatDateTime(data.created_at)
                }}</template>
            </Column>
            <Column
                field="user_name"
                header="Admin"
                :style="{ width: '9rem' }"
            />
            <Column header="Action" :style="{ width: '7rem' }">
                <template #body="{ data }">
                    <Tag
                        :value="data.changes.action"
                        :severity="severityFor(data.changes.action)"
                        class="capitalize"
                    />
                </template>
            </Column>
            <Column header="Details">
                <template #body="{ data }">{{
                    describe(data.changes)
                }}</template>
            </Column>
        </DataTable>
    </div>
</template>
