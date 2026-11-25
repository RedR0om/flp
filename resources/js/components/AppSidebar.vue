<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    ChartColumn,
    Cloud,
    House,
    Info,
    Layers,
    School,
    Users,
    Wrench,
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
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    { title: 'Overview', href: dashboard(), icon: House },
    { title: 'Section Details', href: '/sections', icon: Users },
    { title: 'Modality Monitoring', href: '/modalities', icon: Layers },
    { title: 'School Status', href: '/schools', icon: School },
    { title: 'Validation / Home Visitation', href: '/validations', icon: BadgeCheck },
    { title: 'Action / Technical Assistance', href: '/actions', icon: Wrench },
    { title: 'Reports & Export', href: '/reports', icon: ChartColumn },
    { title: '4H Hazard Mapping', href: '/hazards', icon: Cloud },
    { title: 'About', href: '/about', icon: Info },
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

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <p class="px-3 pb-2 text-xs font-semibold text-sidebar-foreground">
                DepEd · SDO Taguig City and Pateros
            </p>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
