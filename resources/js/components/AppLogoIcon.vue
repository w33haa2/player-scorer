<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
    /**
     * Use the higher-resolution file for large placements (hero, sign-in).
     * The default small file is plenty for header/sidebar sizes.
     */
    large?: boolean;
};

withDefaults(defineProps<Props>(), { large: false });
</script>

<template>
    <!-- Official DBBL crest (transparent WebP generated from resources/images). -->
    <img
        :src="
            large ? '/images/dbbl-logo-512.webp' : '/images/dbbl-logo-128.webp'
        "
        alt="Davao Beyblade Battle League"
        :width="large ? 512 : 128"
        :height="large ? 512 : 128"
        decoding="async"
        draggable="false"
        class="object-contain select-none"
        :class="className"
        v-bind="$attrs"
    />
</template>
