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

import Dashboard from '@/pages/dashboard';
import { type Widget } from '@/types/widgets';

const mockUser = {
    id: 1,
    first_name: 'John',
    last_name: 'Doe',
    email: 'john@example.com',
};

const mockStatsWidget: Widget = {
    type: 'stats',
    title: 'Total Users',
    data: {
        value: 42,
        description: 'Active users in system',
    },
    priority: 1,
};

const mockActionsWidget: Widget = {
    type: 'actions',
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

    test('limits widgets to maximum of 9', () => {
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

        // Should only render 9 widgets (limited in controller, but test the UI)
        const cards = screen.getAllByTestId('card');
        expect(cards.length).toBeLessThanOrEqual(9);
    });
});
