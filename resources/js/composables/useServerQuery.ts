import { router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

type QueryValue = string | number | boolean | null | undefined;

type Options = {
    /** Partial reload keys, e.g. ['scores', 'filters']. */
    only?: string[];
    /** Debounce (ms) used by debouncedApply. Defaults to 300. */
    debounce?: number;
};

/** Don't show a loader for responses faster than this (avoids flashing). */
const LOADING_DELAY_MS = 120;
/** Once a loader is visible, keep it up at least this long (avoids blinking). */
const LOADING_MIN_MS = 300;

/**
 * Small helper for driving server-side search / filter / sort / pagination
 * with Inertia partial reloads while preserving scroll and state.
 *
 * Exposes a `loading` flag suitable for skeleton placeholders.
 */
export function useServerQuery<T extends Record<string, QueryValue>>(
    url: string,
    initial: T,
    options: Options = {},
) {
    const filters = reactive({ ...initial }) as T;
    const loading = ref(false);

    let debounceTimer: ReturnType<typeof setTimeout> | undefined;
    let loadingTimer: ReturnType<typeof setTimeout> | undefined;
    let loadingShownAt = 0;

    function buildParams(): Record<string, QueryValue> {
        const params: Record<string, QueryValue> = {};

        for (const [key, value] of Object.entries(filters)) {
            if (value !== '' && value !== null && value !== undefined) {
                params[key] = value as QueryValue;
            }
        }

        return params;
    }

    function startLoading(): void {
        clearTimeout(loadingTimer);

        loadingTimer = setTimeout(() => {
            loading.value = true;
            loadingShownAt = Date.now();
        }, LOADING_DELAY_MS);
    }

    function stopLoading(): void {
        clearTimeout(loadingTimer);

        if (!loading.value) {
            return;
        }

        const remaining = Math.max(
            0,
            LOADING_MIN_MS - (Date.now() - loadingShownAt),
        );

        loadingTimer = setTimeout(() => {
            loading.value = false;
        }, remaining);
    }

    function apply(): void {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = undefined;
        }

        router.get(url, buildParams(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: options.only,
            onStart: startLoading,
            onFinish: stopLoading,
        });
    }

    function debouncedApply(): void {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(apply, options.debounce ?? 300);
    }

    /** Reset paging back to the first page, then apply immediately. */
    function applyFromFirstPage(): void {
        if ('page' in filters) {
            (filters as Record<string, QueryValue>).page = 1;
        }

        apply();
    }

    return { filters, loading, apply, debouncedApply, applyFromFirstPage };
}

/**
 * Placeholder rows for a DataTable while it's loading. Keeps the current
 * row count (so the table height doesn't jump), with a sensible minimum.
 */
export function skeletonRows(
    currentCount: number,
    perPage: number,
): { id: string }[] {
    const count = Math.min(perPage, Math.max(currentCount, 5));

    return Array.from({ length: count }, (_, index) => ({
        id: `skeleton-${index}`,
    }));
}
