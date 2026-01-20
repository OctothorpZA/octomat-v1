import { fireEvent, render, screen } from '@testing-library/react';

import RoleAssignment from '@/pages/admin/role-assignment';

const mockUsers = [
    { id: 1, full_name: 'John Doe', email: 'john@example.com' },
    { id: 2, full_name: 'Jane Smith', email: 'jane@example.com' },
];

const mockRoles = {
    'Super Admin': 'System Administrator',
    Coach: 'Coach',
};

test('renders role assignment form', () => {
    render(<RoleAssignment users={mockUsers} roles={mockRoles} />);

    expect(screen.getByText('Role Assignment')).toBeInTheDocument();
    expect(screen.getByText('Assign Role to User')).toBeInTheDocument();
});

test('can select user and role', () => {
    render(<RoleAssignment users={mockUsers} roles={mockRoles} />);

    const userSelect = screen.getByLabelText('Select User');
    const roleSelect = screen.getByLabelText('Assign Role');

    fireEvent.click(userSelect);
    fireEvent.click(screen.getByText('John Doe'));

    fireEvent.click(roleSelect);
    fireEvent.click(screen.getByText('Coach'));

    expect(userSelect).toHaveValue('1');
    expect(roleSelect).toHaveValue('Coach');
});
