<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from '@lucide/vue';
import Avatar from 'primevue/avatar';
import Menu from 'primevue/menu';
import type { MenuItem } from 'primevue/menuitem';
import { computed, ref } from 'vue';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();

const menu = ref<InstanceType<typeof Menu> | null>(null);

const items = ref<MenuItem[]>([
    {
        label: 'Settings',
        icon: 'pi pi-cog',
        command: () => router.visit(edit().url),
    },
    { separator: true },
    {
        label: 'Log out',
        icon: 'pi pi-sign-out',
        command: () => {
            router.flushAll();
            router.post(logout().url);
        },
    },
]);

function toggle(event: Event): void {
    menu.value?.toggle(event);
}
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <SidebarMenuButton
                size="lg"
                data-test="sidebar-menu-button"
                aria-haspopup="true"
                class="data-[state=open]:bg-sidebar-accent"
                @click="toggle"
            >
                <Avatar
                    :label="getInitials(user.name)"
                    shape="circle"
                    class="!bg-surface-200 !text-surface-700 dark:!bg-surface-700 dark:!text-surface-100 !size-8 shrink-0 !text-xs !font-semibold"
                />
                <div class="grid min-w-0 flex-1 text-left leading-tight">
                    <span class="truncate text-sm font-medium">{{
                        user.name
                    }}</span>
                    <span class="truncate text-xs text-muted-foreground">{{
                        user.email
                    }}</span>
                </div>
                <ChevronsUpDown class="ml-auto size-4 shrink-0 opacity-60" />
            </SidebarMenuButton>

            <Menu
                ref="menu"
                :model="items"
                :popup="true"
                append-to="body"
                class="!min-w-60"
            >
                <template #start>
                    <div
                        class="border-surface-200 dark:border-surface-700 flex items-center gap-2 border-b px-3 py-2.5"
                    >
                        <Avatar
                            :label="getInitials(user.name)"
                            shape="circle"
                            class="!bg-surface-200 !text-surface-700 dark:!bg-surface-700 dark:!text-surface-100 !size-9 shrink-0 !text-sm !font-semibold"
                        />
                        <div class="grid min-w-0 leading-tight">
                            <span class="truncate text-sm font-medium">{{
                                user.name
                            }}</span>
                            <span
                                class="text-surface-500 dark:text-surface-400 truncate text-xs"
                                >{{ user.email }}</span
                            >
                        </div>
                    </div>
                </template>
            </Menu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
