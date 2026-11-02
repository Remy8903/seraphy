<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    CloudOff,
    FolderGit2,
    Gamepad2,
    LayoutGrid,
    Stethoscope,
    Users,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { index as activities } from '@/actions/App/Http/Controllers/ActivityController';
import { index as clients } from '@/actions/App/Http/Controllers/ClientController';
import { index as clinicalHours } from '@/actions/App/Http/Controllers/ClinicalLogFormController';
import { dashboard, offline } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Clinical Hours',
        href: clinicalHours(),
        icon: Stethoscope,
    },
    {
        title: 'Clients',
        href: clients(),
        icon: Users,
    },
    {
        title: 'Activities',
        href: activities(),
        icon: Gamepad2,
    },
    {
        title: 'Offline play',
        href: offline(),
        icon: CloudOff,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
