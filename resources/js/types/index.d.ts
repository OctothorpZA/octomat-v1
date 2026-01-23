import { InertiaLinkProps, InertiaSharedProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    roles?: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    roles?: string[];
}

export interface ImpersonationData {
    isImpersonating: boolean;
    originalUser: User | null;
}

export interface SharedData extends InertiaSharedProps {
    name: string;
    auth: Auth;
    impersonate: ImpersonationData;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

// Extend Inertia's shared props to include our impersonation data
declare module '@inertiajs/react' {
    interface InertiaSharedProps {
        impersonate?: ImpersonationData;
    }
}

export interface StatsWidgetData {
    value: number;
    description: string;
}

export interface ActionsWidgetData {
    actions: Array<{ label: string; route: string }>;
}

export interface Widget {
    type:
        | 'stats'
        | 'actions'
        | 'profile'
        | 'performance'
        | 'family'
        | 'welcome';
    title: string;
    data?: StatsWidgetData | ActionsWidgetData | Record<string, unknown>;
    actions?: Array<{ label: string; route: string }>;
    priority: number;
}

export interface User {
    id: number;
    name: string; // Backward compatibility accessor
    first_name: string;
    middle_names?: string | null;
    last_name: string;
    date_of_birth?: string | null;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
