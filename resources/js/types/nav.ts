export interface NavigationItem {
    title: string;
    href: string;
    icon?: string;
    permission?: string | null;
    priority: number;
    children?: NavigationItem[];
}

export type NavItem = NavigationItem;
