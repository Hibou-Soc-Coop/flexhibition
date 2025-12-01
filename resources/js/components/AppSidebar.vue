<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import languagesRoutes from '@/routes/languages';
import mediaRoutes from '@/routes/media';
import museumsRoutes from '@/routes/museums';
import exhibitionsRoutes from '@/routes/exhibitions';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Folder, Landmark, Eye, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Musei',
        href: museumsRoutes.index(),
        icon: Landmark,
    },
    {
        title: 'Collezioni',
        href: exhibitionsRoutes.index(),
        icon: Eye,
    },
];

const settingNavItems: NavItem[] = [
    {
        title: 'Gestisci lingue',
        href: languagesRoutes.index(),
        icon: Folder,
    },
    {
        title: 'Gestisci media',
        href: mediaRoutes.index(),
        icon: Folder,
    },
];

const footerNavItems: NavItem[] = [];
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

        <SidebarContent class="justify-between">
            <NavMain label="Contenuti" :items="mainNavItems" />
            <NavMain label="Impostazioni" :items="settingNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
