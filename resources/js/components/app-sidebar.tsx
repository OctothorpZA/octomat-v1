import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Building,
    Folder,
    LayoutGrid,
    Shield,
    Target,
    Trophy,
    User,
    Users,
} from 'lucide-react';

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
import { type NavItem, type SharedData } from '@/types';

import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const userRoles = auth?.roles || [];

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
            roles: ['*'], // All users
        },
        // Role-aware navigation items
        {
            title: 'User Management',
            href: '/admin/roles/assign',
            icon: Shield,
            roles: ['Super Admin'],
        },
        {
            title: 'Audit Logs',
            href: '#', // Placeholder for Sprint 4
            icon: Users,
            roles: ['Super Admin'],
        },
        {
            title: 'My Athletes',
            href: '#', // Placeholder for Sprint 4
            icon: Users,
            roles: ['Coach'],
        },
        {
            title: 'Training Plans',
            href: '#', // Placeholder for Sprint 4
            icon: Target,
            roles: ['Coach'],
        },
        {
            title: 'My Performance',
            href: '#', // Placeholder for Sprint 4
            icon: Trophy,
            roles: ['Athlete'],
        },
        {
            title: 'Competitions',
            href: '#', // Placeholder for Sprint 4
            icon: Target,
            roles: ['Athlete'],
        },
        {
            title: 'Academy Management',
            href: '#', // Placeholder for Sprint 4
            icon: Building,
            roles: ['Academy Owner'],
        },
        {
            title: 'Club Oversight',
            href: '#', // Placeholder for Sprint 4
            icon: Building,
            roles: ['Club Manager'],
        },
        {
            title: 'Family Profile',
            href: '#', // Placeholder for Sprint 4
            icon: User,
            roles: ['Parent/Guardian'],
        },
    ];

    // Filter navigation based on user roles
    const filteredNavItems = mainNavItems.filter((item) => {
        if (!item.roles) return true; // No role restrictions
        if (item.roles.includes('*')) return true;
        return userRoles.some((role: string) => item.roles!.includes(role));
    });
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
                <NavMain items={filteredNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
