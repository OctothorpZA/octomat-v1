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
    Heart,
    Home,
    LayoutDashboard,
    LogOut,
    MapPin,
    MessageSquare,
    Palette,
    Settings,
    Shield,
    Target,
    TrendingUp,
    Trophy,
    User,
    UserCheck,
    Users,
} from 'lucide-react';
import React from 'react';

import { Badge } from '@/components/ui/badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

// Import required icons (using lucide-react)

interface NavigationItem {
    title: string;
    href: string;
    icon?: string;
    permission?: string | null;
    priority: number;
    children?: NavigationItem[];
}

interface NavigationSidebarProps {
    navigation: NavigationItem[];
    className?: string;
}

export function NavigationSidebar({
    navigation,
    className,
}: NavigationSidebarProps) {
    const { auth } = usePage().props as unknown as {
        auth?: Record<string, unknown>;
    };
    const currentPath = window.location.pathname;

    const renderNavigationItem = (item: NavigationItem, _level = 0) => {
        const hasChildren = item.children && item.children.length > 0;
        const isActive =
            currentPath === item.href ||
            item.children?.some((child) => currentPath === child.href);
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
                                                currentPath === child.href
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

    return (
        <Sidebar className={className}>
            <SidebarHeader>
                <div className="flex items-center gap-2 px-4 py-2">
                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <span className="text-sm font-bold">O</span>
                    </div>
                    <div className="grid flex-1 text-left text-sm leading-tight">
                        <span className="truncate font-semibold">OctoMat</span>
                        <span className="truncate text-xs text-muted-foreground">
                            Sports Management
                        </span>
                    </div>
                </div>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup>
                    <SidebarGroupLabel>Navigation</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {navigation.map((item) =>
                                renderNavigationItem(item),
                            )}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton
                                    size="lg"
                                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                                >
                                    <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                        <span className="text-xs font-bold">
                                            {auth?.user?.first_name?.[0] ||
                                                auth?.user?.email?.[0] ||
                                                'U'}
                                        </span>
                                    </div>
                                    <div className="grid flex-1 text-left text-sm leading-tight">
                                        <span className="truncate font-semibold">
                                            {auth?.user?.full_name ||
                                                auth?.user?.email ||
                                                'User'}
                                        </span>
                                        <span className="truncate text-xs text-muted-foreground">
                                            {auth?.user?.roles?.[0]
                                                ?.display_name || 'User'}
                                        </span>
                                    </div>
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                                side="bottom"
                                align="end"
                                sideOffset={4}
                            >
                                <DropdownMenuItem asChild>
                                    <Link href="/settings/profile">
                                        <User className="h-4 w-4" />
                                        Profile Settings
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href="/settings/appearance">
                                        <Palette className="h-4 w-4" />
                                        Appearance
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href="/logout" method="post">
                                        <LogOut className="h-4 w-4" />
                                        Log out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
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

    return icons[name] || <div className={className}>•</div>;
}
