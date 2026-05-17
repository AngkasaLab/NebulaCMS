import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarRail } from '@/components/ui/sidebar';
import { type AdminNavigationItem, type NavItem } from '@/types';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, File, FileText, Settings, Users, Shield, Image, Paintbrush, AlignJustify, Puzzle, ScrollText } from 'lucide-react';
import { useMemo } from 'react';
import AppLogo from './app-logo';

function can(permissions: string[], name: string): boolean {
    return permissions.includes(name);
}

const iconMap = {
    AlignJustify,
    File,
    FileText,
    Image,
    LayoutGrid,
    Paintbrush,
    Puzzle,
    ScrollText,
    Settings,
    Shield,
    Users,
} as const;

function toNavItem(item: AdminNavigationItem): NavItem {
    const icon = item.icon ? iconMap[item.icon as keyof typeof iconMap] : undefined;

    return {
        title: item.title,
        href: item.href,
        icon,
        badge: item.badge ?? undefined,
        matchPaths: item.match_paths,
        items: item.items,
    };
}

export function AppSidebar() {
    const { auth, updateAvailable, adminNavigation } = usePage<SharedData>().props;
    const permissions = useMemo(() => auth.permissions ?? [], [auth.permissions]);

    const { mainNavItems, contentNavItems, adminNavItems } = useMemo(() => {
        const main: NavItem[] = [];
        if (can(permissions, 'view dashboard')) {
            main.push({
                title: 'Dashboard',
                href: route('admin.dashboard'),
                icon: LayoutGrid,
            });
        }

        const content: NavItem[] = [];
        if (can(permissions, 'view content')) {
            content.push({
                title: 'Pages',
                href: route('admin.pages.index'),
                icon: File,
            });
        }

        const postSubs: { title: string; href: string }[] = [];
        if (can(permissions, 'view content')) {
            postSubs.push({ title: 'All Posts', href: route('admin.posts.index') });
        }
        if (can(permissions, 'view categories')) {
            postSubs.push({ title: 'Categories', href: route('admin.categories.index') });
            postSubs.push({ title: 'Tags', href: route('admin.tags.index') });
        }
        if (postSubs.length > 0) {
            content.push({
                title: 'Posts',
                icon: FileText,
                items: postSubs,
            });
        }

        if (can(permissions, 'view media')) {
            content.push({
                title: 'Media',
                href: route('admin.media.index'),
                icon: Image,
            });
        }

        if (can(permissions, 'manage menus')) {
            content.push({
                title: 'Menus',
                href: route('admin.menus.index'),
                icon: AlignJustify,
            });
        }

        const admin: NavItem[] = [];
        if (can(permissions, 'view users')) {
            admin.push({
                title: 'Users',
                href: route('admin.users.index'),
                icon: Users,
            });
        }
        if (can(permissions, 'manage roles')) {
            admin.push({
                title: 'Roles',
                href: route('admin.roles.index'),
                icon: Shield,
            });
        }
        if (can(permissions, 'view audit log')) {
            admin.push({
                title: 'Audit log',
                href: route('admin.audit-log.index'),
                icon: ScrollText,
            });
        }
        if (can(permissions, 'manage themes')) {
            admin.push({
                title: 'Themes',
                href: route('admin.themes.index'),
                icon: Paintbrush,
            });
        }
        if (can(permissions, 'manage plugins')) {
            admin.push({
                title: 'Plugins',
                href: route('admin.plugins.index'),
                icon: Puzzle,
            });
        }
        if (can(permissions, 'manage settings')) {
            admin.push({
                title: 'Settings',
                href: route('admin.settings.general'),
                icon: Settings,
                badge: updateAvailable?.available === true ? 'New' : undefined,
            });
        }

        for (const item of adminNavigation ?? []) {
            const navItem = toNavItem(item);

            if (item.group === 'main') {
                main.push(navItem);
                continue;
            }

            if (item.group === 'content') {
                content.push(navItem);
                continue;
            }

            admin.push(navItem);
        }

        return { mainNavItems: main, contentNavItems: content, adminNavItems: admin };
    }, [permissions, updateAvailable, adminNavigation]);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild tooltip="NebulaCMS">
                            <Link href={route('admin.dashboard')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {mainNavItems.length > 0 && <NavMain items={mainNavItems} />}
                {contentNavItems.length > 0 && <NavMain items={contentNavItems} label="Content Management" />}
                {adminNavItems.length > 0 && <NavMain items={adminNavItems} />}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
