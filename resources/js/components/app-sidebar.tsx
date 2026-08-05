import { Link, usePage } from '@inertiajs/react';
import { BookOpen, FolderGit2, LayoutGrid, Tags, Package, ShoppingCart, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
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
import categories from '@/routes/categories';
import products from '@/routes/products';
import orders from '@/routes/orders';
import admins from '@/routes/admins';
import type { NavItem } from '@/types';
import type { Auth } from '@/types/auth';

type NavItemWithRoles = NavItem & { roles?: string[] };

const allNavItems: NavItemWithRoles[] = [
    {
        title: 'Ana Panel',
        href: dashboard(),
        icon: LayoutGrid,
        roles: ['Süper Admin'],
    },
    {
        title: 'Kategoriler',
        href: categories.index(),
        icon: Tags,
        roles: ['Süper Admin'],
    },
    {
        title: 'Ürünler',
        href: products.index(),
        icon: Package,
    },
    {
        title: 'Siparişler',
        href: orders.index(),
        icon: ShoppingCart,
    },
    {
        title: 'Sistem Yöneticileri',
        href: admins.index(),
        icon: Users,
        roles: ['Süper Admin'],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props as unknown as { auth: Auth };
    const roles = auth.roles ?? [];

    const mainNavItems = allNavItems.filter(
        (item) => !item.roles || item.roles.some((role) => roles.includes(role)),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
