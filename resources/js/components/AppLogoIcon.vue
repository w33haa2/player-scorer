<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- DBBL mark: an open ring (spin) around a center point. -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M21.66 9.41A10 10 0 1 1 14.59 2.34L13.68 5.72A6.5 6.5 0 1 0 18.28 10.32Z"
        />
        <path
            fill="currentColor"
            d="M12 9.25a2.75 2.75 0 1 1 0 5.5 2.75 2.75 0 0 1 0-5.5Z"
        />
    </svg>
</template>
