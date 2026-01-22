import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search } from 'lucide-react';
import React, { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    full_name?: string;
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

export default function RoleAssignment({
    users,
    roles,
    filters,
}: {
    users: PaginatedUsers;
    roles: Record<string, string>;
    filters?: { search?: string };
}) {
    const [search, setSearch] = useState(filters?.search || '');
    const debouncedSearch = useDebounce(search, 300);

    const { data, setData, post, processing, errors } = useForm({
        selectedUser: '',
        selectedRole: '',
    });

    // Auto-submit search with debouncing
    useEffect(() => {
        // Use window.location for direct navigation with query params
        const url = new URL(window.location.href);
        if (debouncedSearch) {
            url.searchParams.set('search', debouncedSearch);
        } else {
            url.searchParams.delete('search');
        }
        router.visit(url.toString(), {
            preserveState: true,
            replace: true,
        });
    }, [debouncedSearch]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(assign().url);
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
                <div>
                    <h1 className="text-2xl font-bold">Role Assignment</h1>
                    <p className="text-muted-foreground">
                        Assign roles to users in the system.
                    </p>
                </div>

                {/* Search Input */}
                <div className="flex items-center space-x-2">
                    <div className="relative max-w-sm flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="text"
                            placeholder="Search users by name or email..."
                            value={search}
                            onChange={(
                                e: React.ChangeEvent<HTMLInputElement>,
                            ) => setSearch(e.target.value)}
                            className="pl-9"
                        />
                    </div>
                </div>

                <Card className="max-w-md">
                    <CardHeader>
                        <CardTitle>Assign Role to User</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label>Select User</Label>
                                <Select
                                    value={data.selectedUser}
                                    onValueChange={(value) =>
                                        setData('selectedUser', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choose User" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.data.map((user) => (
                                            <SelectItem
                                                key={user.id}
                                                value={user.id.toString()}
                                            >
                                                {user.full_name || user.email}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.selectedUser && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.selectedUser}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>Assign Role</Label>
                                <Select
                                    value={data.selectedRole}
                                    onValueChange={(value) =>
                                        setData('selectedRole', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(roles).map(
                                            ([key, label]) => (
                                                <SelectItem
                                                    key={key}
                                                    value={key}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                {errors.selectedRole && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.selectedRole}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Assigning...' : 'Assign Role'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

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
