import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import React, { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDebounce } from '@/hooks/use-debounce';
import AppLayout from '@/layouts/app-layout';
import { assign } from '@/routes/admin/roles';
import { type BreadcrumbItem } from '@/types';

interface Role {
    id: number;
    name: string;
    display_name: string;
}

interface User {
    id: number;
    first_name?: string;
    middle_names?: string | null;
    last_name?: string;
    email: string;
    full_name: string;
    roles: Role[];
}

interface RoleAssignmentProps {
    users: PaginatedUsers;
    roles: Record<string, string>;
    filters: {
        search?: string;
        role?: string;
    };
}

interface User {
    id: number;
    first_name?: string;
    middle_names?: string | null;
    last_name?: string;
    email: string;
    roles: Role[];
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLinks[];
}

interface RoleAssignmentProps {
    users: PaginatedUsers;
    roles: Record<string, string>;
    filters: {
        search?: string;
        role?: string;
    };
}

export default function RoleAssignment({
    users: initialUsers,
    roles,
    filters,
}: RoleAssignmentProps) {
    const [search, setSearch] = useState(filters?.search || '');
    const [roleFilter, setRoleFilter] = useState<string>(filters?.role || '');
    const [users, setUsers] = useState(initialUsers);
    const [connectionStatus, setConnectionStatus] = useState<
        'connecting' | 'connected' | 'disconnected' | 'error'
    >('connecting');
    const debouncedSearch = useDebounce(search, 300);
    const { auth } = usePage().props as any;

    // Auto-submit search with debouncing
    useEffect(() => {
        router.get(
            '/admin/roles/assign',
            {
                search: debouncedSearch,
                role: roleFilter,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    }, [debouncedSearch, roleFilter]);

    // Real-time broadcasting listeners
    useEffect(() => {
        if (!auth?.user?.id) return;

        setConnectionStatus('connecting');

        const channel = (window as any).Echo.private(`admin.${auth.user.id}`);

        // Connection established
        channel.subscribed(() => {
            console.log('Connected to real-time admin channel');
            setConnectionStatus('connected');
        });

        // Connection error
        channel.error((error: any) => {
            console.error('Real-time connection error:', error);
            setConnectionStatus('error');
            if ((window as any).showToast) {
                (window as any).showToast('Real-time connection lost', 'error');
            }
        });

        // Listen for role assignment events
        channel.listen('.role.assigned', (event: any) => {
            console.log('Role assigned:', event);

            // Show toast notification
            if ((window as any).showToast) {
                (window as any).showToast(
                    `Role "${event.role}" assigned to ${event.user.name}`,
                    'success',
                );
            }

            // Update user data directly without page reload
            setUsers((currentUsers) => ({
                ...currentUsers,
                data: currentUsers.data.map((user) =>
                    user.id === event.user.id
                        ? {
                              ...user,
                              roles: [
                                  ...user.roles,
                                  {
                                      id: Date.now(),
                                      name: event.role,
                                      display_name: event.role,
                                  },
                              ],
                          }
                        : user,
                ),
            }));
        });

        // Listen for role removal events
        channel.listen('.role.removed', (event: any) => {
            console.log('Role removed:', event);

            // Show toast notification
            if ((window as any).showToast) {
                (window as any).showToast(
                    `Role "${event.role}" removed from ${event.user.name}`,
                    'info',
                );
            }

            // Update user data directly without page reload
            setUsers((currentUsers) => ({
                ...currentUsers,
                data: currentUsers.data.map((user) =>
                    user.id === event.user.id
                        ? {
                              ...user,
                              roles: user.roles.filter(
                                  (role) => role.name !== event.role,
                              ),
                          }
                        : user,
                ),
            }));
        });

        // Cleanup on unmount
        return () => {
            setConnectionStatus('disconnected');
            (window as any).Echo.leave(`admin.${auth.user.id}`);
        };
    }, [auth?.user?.id]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(assign().url, {});
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Admin',
            href: '/admin',
        },
        {
            title: 'Role Assignment',
            href: assign().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Role Assignment" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Role Assignment</h1>
                        <p className="text-muted-foreground">
                            Assign roles to users in the system.
                        </p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Badge
                            variant={
                                connectionStatus === 'connected'
                                    ? 'default'
                                    : connectionStatus === 'connecting'
                                      ? 'secondary'
                                      : 'destructive'
                            }
                            className="text-xs"
                        >
                            {connectionStatus === 'connected' && '🟢 Live'}
                            {connectionStatus === 'connecting' &&
                                '🟡 Connecting'}
                            {connectionStatus === 'disconnected' &&
                                '⚪ Disconnected'}
                            {connectionStatus === 'error' &&
                                '🔴 Connection Error'}
                        </Badge>
                    </div>
                </div>

                {/* Search and Filter Controls */}
                <Card>
                    <CardHeader>
                        <CardTitle>Search & Filter Users</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col space-y-4 md:flex-row md:space-y-0 md:space-x-4">
                            <div className="flex-1">
                                <label
                                    htmlFor="search"
                                    className="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Search Users
                                </label>
                                <input
                                    id="search"
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Search by name or email..."
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                />
                            </div>
                            <div className="flex-1">
                                <label
                                    htmlFor="role-filter"
                                    className="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Filter by Role
                                </label>
                                <select
                                    id="role-filter"
                                    value={roleFilter}
                                    onChange={(e) =>
                                        setRoleFilter(e.target.value)
                                    }
                                    className="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                >
                                    <option value="">All Roles</option>
                                    {Object.entries(roles).map(
                                        ([key, value]) => (
                                            <option key={key} value={key}>
                                                {value}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </div>
                        </div>
                        {(search || roleFilter) && (
                            <div className="mt-4 flex items-center space-x-2">
                                <span className="text-sm text-gray-600">
                                    Active filters:
                                    {search && (
                                        <span className="ml-1 font-medium">
                                            Search: "{search}"
                                        </span>
                                    )}
                                    {roleFilter && (
                                        <span className="ml-1 font-medium">
                                            Role: {roles[roleFilter]}
                                        </span>
                                    )}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        setSearch('');
                                        setRoleFilter('');
                                    }}
                                >
                                    Clear Filters
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Role assignment form - Phase 2
                <Card className="max-w-md">
                    <CardHeader>
                        <CardTitle>Assign Role to User</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            Role assignment form will be implemented in Phase 2.
                        </p>
                    </CardContent>
                </Card>
                */}

                {/* Current Role Assignments Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Current Role Assignments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table role="table" aria-label="User role assignments">
                            <TableHeader>
                                <TableRow role="row">
                                    <TableHead
                                        role="columnheader"
                                        aria-sort="none"
                                    >
                                        User
                                    </TableHead>
                                    <TableHead
                                        role="columnheader"
                                        aria-sort="none"
                                    >
                                        Email
                                    </TableHead>
                                    <TableHead
                                        role="columnheader"
                                        aria-sort="none"
                                    >
                                        Current Roles
                                    </TableHead>
                                    <TableHead role="columnheader">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id} role="row">
                                        <TableCell role="cell">
                                            {user.first_name && user.last_name
                                                ? `${user.first_name} ${user.last_name}`
                                                : user.email}
                                        </TableCell>
                                        <TableCell role="cell">
                                            {user.email}
                                        </TableCell>
                                        <TableCell role="cell">
                                            {user.roles.length > 0
                                                ? user.roles
                                                      .map(
                                                          (role) =>
                                                              role.display_name,
                                                      )
                                                      .join(', ')
                                                : 'No roles assigned'}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            `Impersonate ${user.first_name || user.email}?`,
                                                        )
                                                    ) {
                                                        router.post(
                                                            `/impersonate/take/${user.id}`,
                                                        );
                                                    }
                                                }}
                                            >
                                                Impersonate
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {users.last_page > 1 && (
                            <div className="flex items-center justify-between pt-4">
                                <div className="text-sm text-muted-foreground">
                                    Showing{' '}
                                    {(users.current_page - 1) * users.per_page +
                                        1}{' '}
                                    to{' '}
                                    {Math.min(
                                        users.current_page * users.per_page,
                                        users.total,
                                    )}{' '}
                                    of {users.total} users
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={users.current_page === 1}
                                        asChild={users.current_page > 1}
                                    >
                                        {users.current_page > 1 ? (
                                            <Link
                                                href={
                                                    users.links[
                                                        users.current_page - 2
                                                    ]?.url || '#'
                                                }
                                            >
                                                <ChevronLeft className="h-4 w-4" />
                                                Previous
                                            </Link>
                                        ) : (
                                            <>
                                                <ChevronLeft className="h-4 w-4" />
                                                Previous
                                            </>
                                        )}
                                    </Button>

                                    <span className="text-sm">
                                        Page {users.current_page} of{' '}
                                        {users.last_page}
                                    </span>

                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={
                                            users.current_page ===
                                            users.last_page
                                        }
                                        asChild={
                                            users.current_page < users.last_page
                                        }
                                    >
                                        {users.current_page <
                                        users.last_page ? (
                                            <Link
                                                href={
                                                    users.links[
                                                        users.current_page
                                                    ]?.url || '#'
                                                }
                                            >
                                                Next
                                                <ChevronRight className="h-4 w-4" />
                                            </Link>
                                        ) : (
                                            <>
                                                Next
                                                <ChevronRight className="h-4 w-4" />
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
