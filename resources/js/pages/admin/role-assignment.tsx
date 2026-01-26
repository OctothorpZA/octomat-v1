import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Loader2, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

// ── Interfaces ──────────────────────────────────────────────────────────────

interface BroadcastEvent {
    admin: { id: number; name: string; email: string };
    user: { id: number; name: string; email: string };
    role: string;
}

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
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface RoleAssignmentProps {
    users: PaginatedUsers;
    roles: Record<string, string>;
    filters: {
        search?: string;
        role?: string;
    };
}

// ── Component ───────────────────────────────────────────────────────────────

export default function RoleAssignment({
    users: initialUsers,
    roles,
    filters,
}: RoleAssignmentProps) {
    const [search, setSearch] = useState(filters?.search || '');
    const [roleFilter, setRoleFilter] = useState(filters?.role || '');
    const [users, setUsers] = useState(initialUsers);
    const [connectionStatus, setConnectionStatus] = useState<
        'connecting' | 'connected' | 'disconnected' | 'error'
    >('connecting');
    const [processingId, setProcessingId] = useState<number | null>(null);

    const debouncedSearch = useDebounce(search, 300);
    const { auth } = usePage().props as { auth?: { user?: { id: number } } };

    const assignForm = useForm({
        selectedUser: '',
        selectedRole: '',
    });

    // Sync prop changes
    useEffect(() => {
        setUsers(initialUsers);
    }, [initialUsers]);

    // Debounced filter updates
    useEffect(() => {
        const currentParams = new URLSearchParams(window.location.search);
        const currentSearch = currentParams.get('search') || '';
        const currentRole = currentParams.get('role') || '';

        if (debouncedSearch === currentSearch && roleFilter === currentRole) {
            return;
        }

        router.get(
            assign().url, // ← using named route (v1 style); fallback: '/admin/roles/assign'
            { search: debouncedSearch, role: roleFilter },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['users', 'filters'],
            }
        );
    }, [debouncedSearch, roleFilter]);

    // Real-time updates
    useEffect(() => {
        if (!auth?.user?.id || !(window as any).Echo) return;

        setConnectionStatus('connecting');

        const channel = (window as any).Echo.private(`admin.${auth.user.id}`);

        channel
            .subscribed(() => {
                console.log('Connected to real-time admin channel');
                setConnectionStatus('connected');
            })
            .error(() => setConnectionStatus('error'));

        channel.listen('.role.assigned', (event: BroadcastEvent) => {
            setUsers((prev) => ({
                ...prev,
                data: prev.data.map((u) =>
                    u.id === event.user.id
                        ? {
                              ...u,
                              roles: [
                                  ...u.roles,
                                  {
                                      id: Date.now(), // temp ID for React key
                                      name: event.role,
                                      display_name: roles[event.role] || event.role,
                                  },
                              ],
                          }
                        : u
                ),
            }));

            (window as any).showToast?.(
                `Role "${event.role}" assigned to ${event.user.name}`,
                'success'
            );
        });

        channel.listen('.role.removed', (event: BroadcastEvent) => {
            setUsers((prev) => ({
                ...prev,
                data: prev.data.map((u) =>
                    u.id === event.user.id
                        ? {
                              ...u,
                              roles: u.roles.filter((r) => r.name !== event.role),
                          }
                        : u
                ),
            }));

            (window as any).showToast?.(
                `Role "${event.role}" removed from ${event.user.name}`,
                'info'
            );
        });

        return () => {
            setConnectionStatus('disconnected');
            (window as any).Echo.leave(`admin.${auth.user.id}`);
        };
    }, [auth?.user?.id, roles]);

    const handleGlobalAssign = (e: React.FormEvent) => {
        e.preventDefault();
        assignForm.post(assign().url, {
            preserveScroll: true,
            onSuccess: () => assignForm.reset(),
        });
    };

    const handleInlineAssign = (userId: number, roleName: string) => {
        if (!roleName) return;
        setProcessingId(userId);
        router.post(
            assign().url,
            { selectedUser: userId, selectedRole: roleName },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            }
        );
    };

    const handleRemove = (userId: number, roleName: string) => {
        if (!confirm(`Remove the "${roles[roleName] || roleName}" role from this user?`)) return;
        setProcessingId(userId);
        router.post(
            '/admin/roles/remove',
            { selectedUser: userId, selectedRole: roleName },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            }
        );
    };

    const clearFilters = () => {
        setSearch('');
        setRoleFilter('');
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Role Assignment', href: assign().url },
    ];

    // ── Precise showing range (restored from v1) ─────────────────────────────
    const from = users.data.length > 0 ? (users.current_page - 1) * users.per_page + 1 : 0;
    const to = users.data.length > 0 ? Math.min(users.current_page * users.per_page, users.total) : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Role Assignment" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Role Assignment</h1>
                        <p className="text-muted-foreground">
                            Manage user permissions and access levels.
                        </p>
                    </div>
                    <Badge
                        variant={
                            connectionStatus === 'connected'
                                ? 'default'
                                : connectionStatus === 'connecting'
                                  ? 'secondary'
                                  : 'destructive'
                        }
                    >
                        {connectionStatus === 'connected' && '🟢 Live'}
                        {connectionStatus === 'connecting' && '🟡 Connecting'}
                        {connectionStatus === 'disconnected' && '⚪ Disconnected'}
                        {connectionStatus === 'error' && '🔴 Connection Error'}
                    </Badge>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium mb-1.5">
                                    Search Users
                                </label>
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Name or email..."
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1.5">
                                    Filter by Role
                                </label>
                                <select
                                    value={roleFilter}
                                    onChange={(e) => setRoleFilter(e.target.value)}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                                >
                                    <option value="">All Roles</option>
                                    {Object.entries(roles).map(([name, display]) => (
                                        <option key={name} value={name}>
                                            {display}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {(search || roleFilter) && (
                            <div className="mt-4 flex items-center gap-3 text-sm text-muted-foreground">
                                <span>Active filters:</span>
                                {search && <span className="font-medium">"{search}"</span>}
                                {roleFilter && <span className="font-medium">{roles[roleFilter]}</span>}
                                <Button variant="ghost" size="sm" onClick={clearFilters}>
                                    Clear
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Global Assign Form */}
                <Card className="max-w-md">
                    <CardHeader>
                        <CardTitle>Assign Role to User</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleGlobalAssign} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium mb-1.5">User</label>
                                <Select
                                    value={assignForm.data.selectedUser}
                                    onValueChange={(v) => assignForm.setData('selectedUser', v)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select user..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {users.data.map((user) => (
                                            <SelectItem key={user.id} value={user.id.toString()}>
                                                {user.full_name ||
                                                    `${user.first_name || ''} ${user.last_name || ''}`.trim() ||
                                                    user.email}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {assignForm.errors.selectedUser && (
                                    <p className="text-sm text-destructive mt-1">
                                        {assignForm.errors.selectedUser}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium mb-1.5">Role</label>
                                <Select
                                    value={assignForm.data.selectedRole}
                                    onValueChange={(v) => assignForm.setData('selectedRole', v)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select role..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(roles).map(([name, display]) => (
                                            <SelectItem key={name} value={name}>
                                                {display}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {assignForm.errors.selectedRole && (
                                    <p className="text-sm text-destructive mt-1">
                                        {assignForm.errors.selectedRole}
                                    </p>
                                )}
                            </div>

                            <Button type="submit" disabled={assignForm.processing} className="w-full">
                                {assignForm.processing ? 'Assigning...' : 'Assign Role'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Users & Roles Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Users & Current Roles</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table
                            role="table"
                            aria-label="User role assignments table"
                        >
                            <TableHeader>
                                <TableRow role="row">
                                    <TableHead role="columnheader" aria-sort="none" className="w-[240px]">
                                        User
                                    </TableHead>
                                    <TableHead role="columnheader" aria-sort="none">
                                        Roles
                                    </TableHead>
                                    <TableHead role="columnheader" aria-sort="none" className="w-[300px]">
                                        Add / Manage
                                    </TableHead>
                                    <TableHead role="columnheader" className="w-[140px] text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id} role="row">
                                        <TableCell role="cell">
                                            <div className="font-medium">
                                                {user.full_name ||
                                                    `${user.first_name || ''} ${user.last_name || ''}`.trim() ||
                                                    user.email}
                                            </div>
                                            <div className="text-xs text-muted-foreground mt-0.5">
                                                {user.email}
                                            </div>
                                        </TableCell>

                                        <TableCell role="cell">
                                            <div className="flex flex-wrap gap-1.5">
                                                {user.roles.length > 0 ? (
                                                    user.roles.map((role) => (
                                                        <Badge
                                                            key={role.name}
                                                            variant="secondary"
                                                            className="flex items-center gap-1 px-2.5 py-0.5"
                                                        >
                                                            {role.display_name}
                                                            <button
                                                                onClick={() => handleRemove(user.id, role.name)}
                                                                className="ml-1 rounded hover:bg-destructive/70 hover:text-white p-0.5 -mr-1 transition-colors"
                                                                disabled={processingId === user.id}
                                                            >
                                                                <X className="h-3.5 w-3.5" />
                                                            </button>
                                                        </Badge>
                                                    ))
                                                ) : (
                                                    <span className="text-sm text-muted-foreground italic">
                                                        No roles assigned
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>

                                        <TableCell role="cell">
                                            <div className="flex items-center gap-2">
                                                <select
                                                    className="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary disabled:opacity-50"
                                                    onChange={(e) => handleInlineAssign(user.id, e.target.value)}
                                                    value=""
                                                    disabled={processingId === user.id}
                                                >
                                                    <option value="" disabled>
                                                        + Add role...
                                                    </option>
                                                    {Object.entries(roles).map(([name, display]) => (
                                                        <option key={name} value={name}>
                                                            {display}
                                                        </option>
                                                    ))}
                                                </select>

                                                {processingId === user.id && (
                                                    <Loader2 className="h-5 w-5 animate-spin text-primary" />
                                                )}
                                            </div>
                                        </TableCell>

                                        <TableCell role="cell" className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    if (confirm(`Impersonate ${user.full_name || user.email}?`)) {
                                                        router.post(`/impersonate/take/${user.id}`);
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
                        <div className="mt-6 flex items-center justify-between border-t pt-4 text-sm text-muted-foreground">
                            <div>
                                Showing {from}–{to} of {users.total} users
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!users.prev_page_url}
                                    asChild={!!users.prev_page_url}
                                >
                                    {users.prev_page_url ? (
                                        <Link href={users.prev_page_url}>
                                            <ChevronLeft className="mr-1 h-4 w-4" />
                                            Previous
                                        </Link>
                                    ) : (
                                        <>
                                            <ChevronLeft className="mr-1 h-4 w-4" />
                                            Previous
                                        </>
                                    )}
                                </Button>

                                <span className="px-3">
                                    Page {users.current_page} / {users.last_page}
                                </span>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!users.next_page_url}
                                    asChild={!!users.next_page_url}
                                >
                                    {users.next_page_url ? (
                                        <Link href={users.next_page_url}>
                                            Next
                                            <ChevronRight className="ml-1 h-4 w-4" />
                                        </Link>
                                    ) : (
                                        <>
                                            Next
                                            <ChevronRight className="ml-1 h-4 w-4" />
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
