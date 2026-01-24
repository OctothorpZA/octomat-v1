import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

interface AuditLog {
    id: number;
    admin_name: string;
    target_user_name: string;
    action: string;
    role: string;
    ip_address: string;
    timestamp: string;
}

interface AuditStats {
    total_logs: number;
    recent_logs: number;
    role_assignments: number;
    role_removals: number;
    unique_admins: number;
    unique_targets: number;
}

interface AuditLogProps {
    auditLogs: {
        data: AuditLog[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: AuditStats;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Audit Logs',
        href: '/admin/audit',
    },
];

export default function AuditLog({
    auditLogs: initialLogs,
    stats: initialStats,
}: AuditLogProps) {
    const [auditLogs, setAuditLogs] = useState(initialLogs);
    const [stats, setStats] = useState(initialStats);
    const [connectionStatus, setConnectionStatus] = useState<
        'connecting' | 'connected' | 'disconnected' | 'error'
    >('connecting');
    const { auth } = usePage().props as any;

    // Real-time audit log updates
    useEffect(() => {
        if (!auth?.user?.id) return;

        setConnectionStatus('connecting');

        // Listen to admin channel for audit events
        const adminChannel = (window as any).Echo.private(
            `admin.${auth.user.id}`,
        );

        adminChannel.subscribed(() => {
            console.log('Connected to admin audit channel');
            setConnectionStatus('connected');
        });

        adminChannel.error((error: any) => {
            console.error('Admin audit channel error:', error);
            setConnectionStatus('error');
        });

        // Listen for role changes (which create audit logs)
        adminChannel.listen('.role.assigned', (event: any) => {
            // Update stats in real-time
            setStats((prevStats) => ({
                ...prevStats,
                total_logs: prevStats.total_logs + 1,
                recent_logs: prevStats.recent_logs + 1,
                role_assignments: prevStats.role_assignments + 1,
            }));

            // Add new audit log entry (simulated - in real app this would come from server)
            const newLog: AuditLog = {
                id: Date.now(),
                admin_name: event.admin.name,
                target_user_name: event.user.name,
                action: 'assigned',
                role: event.role,
                ip_address: '127.0.0.1', // Would come from server
                timestamp: new Date().toISOString(),
            };

            setAuditLogs((prevLogs) => ({
                ...prevLogs,
                data: [newLog, ...prevLogs.data],
                total: prevLogs.total + 1,
            }));
        });

        adminChannel.listen('.role.removed', (event: any) => {
            // Update stats in real-time
            setStats((prevStats) => ({
                ...prevStats,
                total_logs: prevStats.total_logs + 1,
                recent_logs: prevStats.recent_logs + 1,
                role_removals: prevStats.role_removals + 1,
            }));

            // Add new audit log entry
            const newLog: AuditLog = {
                id: Date.now(),
                admin_name: event.admin.name,
                target_user_name: event.user.name,
                action: 'removed',
                role: event.role,
                ip_address: '127.0.0.1',
                timestamp: new Date().toISOString(),
            };

            setAuditLogs((prevLogs) => ({
                ...prevLogs,
                data: [newLog, ...prevLogs.data],
                total: prevLogs.total + 1,
            }));
        });

        return () => {
            setConnectionStatus('disconnected');
            (window as any).Echo.leave(`admin.${auth.user.id}`);
        };
    }, [auth?.user?.id]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Logs" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="mb-4 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Audit Logs</h1>
                        <p className="text-muted-foreground">
                            Monitor all role assignment and removal activities.
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
                        className="text-xs"
                    >
                        {connectionStatus === 'connected' && '🟢 Live Updates'}
                        {connectionStatus === 'connecting' && '🟡 Connecting'}
                        {connectionStatus === 'disconnected' &&
                            '⚪ Disconnected'}
                        {connectionStatus === 'error' && '🔴 Connection Error'}
                    </Badge>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Logs
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_logs}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Recent Logs
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.recent_logs}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Assignments
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.role_assignments}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Removals
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.role_removals}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Audit Logs</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Admin</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>IP Address</TableHead>
                                    <TableHead>Timestamp</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {auditLogs.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell>{log.admin_name}</TableCell>
                                        <TableCell>
                                            {log.target_user_name}
                                        </TableCell>
                                        <TableCell>
                                            <span
                                                className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                                                    log.action === 'assigned'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}
                                            >
                                                {log.action}
                                            </span>
                                        </TableCell>
                                        <TableCell>{log.role}</TableCell>
                                        <TableCell>{log.ip_address}</TableCell>
                                        <TableCell>
                                            {new Date(
                                                log.timestamp,
                                            ).toLocaleString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
