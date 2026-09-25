<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md border border-sidebar-border bg-background"
    >
        <AppLogoIcon class="size-[18px] fill-current text-foreground" />
    </div>
    <div class="ml-1 grid flex-1 text-left leading-tight">
        <span class="truncate text-sm font-semibold tracking-tight"
            >DBBL Scorer</span
        >
        <span class="truncate text-xs text-muted-foreground"
            >Davao Beyblade Battle League</span
        >
    </div>
</template>
