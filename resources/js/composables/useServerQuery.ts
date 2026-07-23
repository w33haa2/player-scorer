import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

type QueryValue = string | number | boolean | null | undefined;

type Options = {
    /** Partial reload keys, e.g. ['scores', 'filters']. */
    only?: string[];
    /** Debounce (ms) used by debouncedApply. Defaults to 300. */
    debounce?: number;
};

/**
 * Small helper for driving server-side search / filter / sort / pagination
 * with Inertia partial reloads while preserving scroll and state.
 */
export function useServerQuery<T extends Record<string, QueryValue>>(
    url: string,
    initial: T,
    options: Options = {},
) {
    const filters = reactive({ ...initial }) as T;

    let timeout: ReturnType<typeof setTimeout> | undefined;

    function buildParams(): Record<string, QueryValue> {
        const params: Record<string, QueryValue> = {};

        for (const [key, value] of Object.entries(filters)) {
            if (value !== '' && value !== null && value !== undefined) {
                params[key] = value as QueryValue;
            }
        }

        return params;
    }

    function apply(): void {
        if (timeout) {
            clearTimeout(timeout);
            timeout = undefined;
        }

        router.get(url, buildParams(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: options.only,
        });
    }

    function debouncedApply(): void {
        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout(apply, options.debounce ?? 300);
    }

    /** Reset paging back to the first page, then apply immediately. */
    function applyFromFirstPage(): void {
        if ('page' in filters) {
            (filters as Record<string, QueryValue>).page = 1;
        }

        apply();
    }

    return { filters, apply, debouncedApply, applyFromFirstPage };
}
