import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    BookOpen,
    Building,
    Calendar,
    ChevronRight,
    Clock,
    Dumbbell,
    FileText,
    Folder,
    Heart,
    Home,
    LayoutDashboard,
    LayoutGrid,
    MapPin,
    MessageSquare,
    Settings,
    Shield,
    Target,
    TrendingUp,
    Trophy,
    User,
    UserCheck,
    Users,
} from 'lucide-react';

import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Badge } from '@/components/ui/badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';

import AppLogo from './app-logo';

interface NavigationItem {
    title: string;
    href: string;
    icon?: string;
    permission?: string | null;
    priority: number;
    children?: NavigationItem[];
}

export function AppSidebar() {
    const { auth, navigation } = usePage<SharedData>().props as SharedData & {
        navigation: NavigationItem[];
    };
    const userRoles = auth?.roles || [];

    // Original static footer
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

    // Original static mainNav (fallback)
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

    const renderNavigationItem = (item: NavigationItem) => {
        const hasChildren = item.children && item.children.length > 0;
        const isActive =
            window.location.pathname === item.href ||
            item.children?.some(
                (child) => window.location.pathname === child.href,
            );
        const isExpanded = isActive;

        if (hasChildren) {
            return (
                <Collapsible
                    key={item.href}
                    defaultOpen={isExpanded}
                    className="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger asChild>
                            <SidebarMenuButton
                                tooltip={item.title}
                                className={cn(
                                    'w-full justify-start',
                                    isActive &&
                                        'bg-sidebar-accent text-sidebar-accent-foreground',
                                )}
                            >
                                {item.icon && (
                                    <Icon
                                        name={item.icon}
                                        className="h-4 w-4"
                                    />
                                )}
                                <span>{item.title}</span>
                                <ChevronRight className="ml-auto h-4 w-4 transition-transform group-data-[state=open]/collapsible:rotate-90" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                {item.children?.map((child) => (
                                    <SidebarMenuSubItem key={child.href}>
                                        <SidebarMenuSubButton
                                            asChild
                                            isActive={
                                                window.location.pathname ===
                                                child.href
                                            }
                                        >
                                            <Link href={child.href}>
                                                {child.icon && (
                                                    <Icon
                                                        name={child.icon}
                                                        className="h-4 w-4"
                                                    />
                                                )}
                                                <span>{child.title}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                ))}
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>
            );
        }

        return (
            <SidebarMenuItem key={item.href}>
                <SidebarMenuButton
                    asChild
                    tooltip={item.title}
                    isActive={isActive}
                >
                    <Link href={item.href}>
                        {item.icon && (
                            <Icon name={item.icon} className="h-4 w-4" />
                        )}
                        <span>{item.title}</span>
                        {item.title === 'Dashboard' && (
                            <Badge
                                variant="secondary"
                                className="ml-auto text-xs"
                            >
                                Home
                            </Badge>
                        )}
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        );
    };

    // Dynamic nav (primary)
    const dynamicNav = navigation as NavigationItem[];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                {/* AppLogo controls header */}
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
                {/* Dynamic primary */}
                <SidebarGroup>
                    <SidebarGroupLabel>Navigation</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {dynamicNav.map(renderNavigationItem)}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
                {/* Original static NavMain fallback/additional */}
                <NavMain items={filteredNavItems} />
            </SidebarContent>

            <SidebarFooter>
                {/* Original */}
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

// Icon component for navigation
function Icon({ name, className }: { name: string; className?: string }) {
    // Simple icon mapping - in a real app you'd use a proper icon library
    const icons: Record<string, React.ReactNode> = {
        LayoutDashboard: <LayoutDashboard className={className} />,
        Shield: <Shield className={className} />,
        BarChart3: <BarChart3 className={className} />,
        Users: <Users className={className} />,
        FileText: <FileText className={className} />,
        Settings: <Settings className={className} />,
        Building: <Building className={className} />,
        Home: <Home className={className} />,
        BookOpen: <BookOpen className={className} />,
        UserCheck: <UserCheck className={className} />,
        Target: <Target className={className} />,
        Calendar: <Calendar className={className} />,
        MapPin: <MapPin className={className} />,
        Dumbbell: <Dumbbell className={className} />,
        Clock: <Clock className={className} />,
        Trophy: <Trophy className={className} />,
        TrendingUp: <TrendingUp className={className} />,
        Activity: <Activity className={className} />,
        Heart: <Heart className={className} />,
        MessageSquare: <MessageSquare className={className} />,
    };

    return (
        (icons as any)[name] || (
            <span
                className={cn(
                    'flex h-4 w-4 items-center justify-center',
                    className,
                )}
            >
                •
            </span>
        )
    );
}
