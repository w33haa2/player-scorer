<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ClipboardList,
    LayoutGrid,
    ScrollText,
    Trophy,
    Users,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as auditLogs } from '@/routes/audit-logs';
import { index as players } from '@/routes/players';
import { index as scores } from '@/routes/scores';
import { index as standings } from '@/routes/standings';
import type { NavItem } from '@/types';

const overviewItems: NavItem[] = [
    { title: 'Overview', href: dashboard(), icon: LayoutGrid },
];

const tournamentItems: NavItem[] = [
    { title: 'Players', href: players(), icon: Users },
    { title: 'Scores', href: scores(), icon: ClipboardList },
];

const recordItems: NavItem[] = [
    { title: 'Standings', href: standings(), icon: Trophy },
    { title: 'Audit log', href: auditLogs(), icon: ScrollText },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="gap-1">
            <NavMain :items="overviewItems" />
            <NavMain label="Tournament" :items="tournamentItems" />
            <NavMain label="Records" :items="recordItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
