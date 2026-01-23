import { render, screen } from '@testing-library/react';
import { vi } from 'vitest';

// Mock Inertia components
vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: { children: React.ReactNode }) => (
        <title>{children}</title>
    ),
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => (
        <a href={href}>{children}</a>
    ),
    router: {
        get: vi.fn(),
        visit: vi.fn(),
    },
    useForm: vi.fn(() => ({
        data: { selectedUser: '', selectedRole: '' },
        setData: vi.fn(),
        post: vi.fn(),
        processing: false,
        errors: {},
    })),
    usePage: vi.fn(() => ({
        props: {
            sidebarOpen: false,
        },
    })),
}));

// Mock the useIsMobile hook used by NavUser component in layout
vi.mock('@/hooks/use-mobile', () => ({
    useIsMobile: () => false,
}));

// Mock sidebar hook used by NavUser
vi.mock('@/components/ui/sidebar', () => ({
    useSidebar: () => ({ state: 'expanded' }),
    SidebarProvider: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    Sidebar: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    SidebarHeader: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    SidebarContent: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    SidebarFooter: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    SidebarMenu: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    SidebarMenuItem: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    SidebarMenuButton: ({ children }: { children: React.ReactNode }) => (
        <button>{children}</button>
    ),
    SidebarGroup: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    SidebarGroupLabel: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
}));

// Mock UI components
vi.mock('@/components/ui/card', () => ({
    Card: ({
        children,
        className,
    }: {
        children: React.ReactNode;
        className?: string;
    }) => (
        <div className={className} data-testid="card">
            {children}
        </div>
    ),
    CardContent: ({ children }: { children: React.ReactNode }) => (
        <div data-testid="card-content">{children}</div>
    ),
    CardHeader: ({ children }: { children: React.ReactNode }) => (
        <div data-testid="card-header">{children}</div>
    ),
    CardTitle: ({ children }: { children: React.ReactNode }) => (
        <h2 data-testid="card-title">{children}</h2>
    ),
}));

// Mock the entire layout to avoid sidebar component issues
vi.mock('@/layouts/app-layout', () => ({
    default: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: ({
        children,
        variant,
    }: {
        children: React.ReactNode;
        variant?: string;
    }) => (
        <span data-testid="badge" data-variant={variant}>
            {children}
        </span>
    ),
}));

vi.mock('@/components/ui/placeholder-pattern', () => ({
    PlaceholderPattern: ({ children }: { children: React.ReactNode }) => (
        <div data-testid="placeholder">{children}</div>
    ),
}));

import Dashboard from '@/pages/dashboard';

const mockUser = {
    id: 1,
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
};

const mockStatsWidget = {
    type: 'stats' as const,
    title: 'Total Users',
    data: {
        value: 42,
        description: 'Active users in system',
    },
    priority: 1,
};

const mockActionsWidget = {
    type: 'actions' as const,
    title: 'Quick Actions',
    actions: [
        { label: 'Add User', route: '/admin/users/create' },
        { label: 'View Reports', route: '/admin/reports' },
    ],
    priority: 2,
};

describe('Dashboard Component', () => {
    test('renders user greeting', () => {
        render(
            <Dashboard
                user={mockUser}
                userRoles={['Super Admin']}
                widgets={[]}
                stats={{ total_users: 42, total_roles: 5 }}
            />,
        );

        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        expect(screen.getByText('Welcome back, John!')).toBeInTheDocument();
    });

    test('renders role badges', () => {
        render(
            <Dashboard
                user={mockUser}
                userRoles={['Super Admin', 'Coach']}
                widgets={[]}
                stats={{ total_users: 42, total_roles: 5 }}
            />,
        );

        const badges = screen.getAllByTestId('badge');
        expect(badges).toHaveLength(2);
        expect(screen.getByText('Super Admin')).toBeInTheDocument();
        expect(screen.getByText('Coach')).toBeInTheDocument();
    });

    test('renders stats widget correctly', () => {
        render(
            <Dashboard
                user={mockUser}
                userRoles={['Super Admin']}
                widgets={[mockStatsWidget]}
                stats={{ total_users: 42, total_roles: 5 }}
            />,
        );

        expect(screen.getByText('Total Users')).toBeInTheDocument();
        expect(screen.getByText('42')).toBeInTheDocument();
        expect(screen.getByText('Active users in system')).toBeInTheDocument();
    });

    test('renders actions widget correctly', () => {
        render(
            <Dashboard
                user={mockUser}
                userRoles={['Super Admin']}
                widgets={[mockActionsWidget]}
                stats={{ total_users: 42, total_roles: 5 }}
            />,
        );

        expect(screen.getByText('Quick Actions')).toBeInTheDocument();
        expect(screen.getByText('Add User')).toBeInTheDocument();
        expect(screen.getByText('View Reports')).toBeInTheDocument();
    });

    test('renders empty state when no widgets', () => {
        render(
            <Dashboard
                user={mockUser}
                userRoles={['General User']}
                widgets={[]}
                stats={{ total_users: 42, total_roles: 5 }}
            />,
        );

        expect(
            screen.getByText('No widgets available for your current roles.'),
        ).toBeInTheDocument();
    });

    test('renders all provided widgets', () => {
        const manyWidgets = Array.from({ length: 12 }, (_, i) => ({
            ...mockStatsWidget,
            title: `Widget ${i + 1}`,
            priority: i,
        }));

        render(
            <Dashboard
                user={mockUser}
                userRoles={['Super Admin']}
                widgets={manyWidgets}
                stats={{ total_users: 42, total_roles: 5 }}
            />,
        );

        // Component renders all widgets passed to it (limiting happens in controller)
        const cards = screen.getAllByTestId('card');
        expect(cards.length).toBeGreaterThanOrEqual(12); // At least 12 widgets rendered
    });
});
