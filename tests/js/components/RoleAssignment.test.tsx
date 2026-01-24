import { render, screen } from '@testing-library/react';
import { vi } from 'vitest';

// Mock all the complex dependencies to test just the component logic
vi.mock('@inertiajs/react', () => ({
    Head: () => null,
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

vi.mock('@/layouts/app-layout', () => ({
    default: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
}));

vi.mock('@/routes/admin/roles', () => ({
    assign: vi.fn(() => ({ url: '/admin/roles/assign' })),
}));

// Mock shadcn/ui components to avoid portal complexity
vi.mock('@/components/ui/card', () => ({
    Card: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    CardContent: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    CardHeader: ({ children }: { children: React.ReactNode }) => (
        <div>{children}</div>
    ),
    CardTitle: ({ children }: { children: React.ReactNode }) => (
        <h2>{children}</h2>
    ),
}));

vi.mock('@/components/ui/table', () => ({
    Table: ({ children }: { children: React.ReactNode }) => (
        <table>{children}</table>
    ),
    TableBody: ({ children }: { children: React.ReactNode }) => (
        <tbody>{children}</tbody>
    ),
    TableCell: ({ children }: { children: React.ReactNode }) => (
        <td>{children}</td>
    ),
    TableHead: ({ children }: { children: React.ReactNode }) => (
        <th>{children}</th>
    ),
    TableHeader: ({ children }: { children: React.ReactNode }) => (
        <thead>{children}</thead>
    ),
    TableRow: ({ children }: { children: React.ReactNode }) => (
        <tr>{children}</tr>
    ),
}));

vi.mock('@/components/admin/UserFilters', () => ({
    UserFilters: ({ children }: { children: React.ReactNode }) => (
        <div data-testid="user-filters">{children}</div>
    ),
}));

import RoleAssignment from '@/pages/admin/role-assignment';

const mockUsers = {
    data: [
        {
            id: 1,
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            roles: [],
        },
        {
            id: 2,
            first_name: 'Jane',
            last_name: 'Smith',
            email: 'jane@example.com',
            roles: [],
        },
    ],
    current_page: 1,
    last_page: 1,
    total: 2,
    links: [],
};

const mockRoles = {
    'Super Admin': 'System Administrator',
    Coach: 'Coach',
};

const mockFilters = {
    search: '',
    role: '',
};

const mockProps = {
    users: mockUsers,
    roles: mockRoles,
    filters: mockFilters,
};

describe('RoleAssignment Component', () => {
    test('renders basic structure', () => {
        render(<RoleAssignment {...mockProps} />);

        // Test that it renders without crashing and has basic structure
        expect(screen.getByText('Role Assignment')).toBeInTheDocument();
        expect(
            screen.getByText('Current Role Assignments'),
        ).toBeInTheDocument();
    });

    test('renders user table', () => {
        render(<RoleAssignment {...mockProps} />);

        // Test that user table is rendered with data
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        expect(screen.getByText('john@example.com')).toBeInTheDocument();
    });
});
