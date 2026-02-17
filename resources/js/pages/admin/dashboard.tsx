import { Head } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

interface Activity {
    id: number;
    action: string;
    role: string;
    admin_name: string;
    target_name: string;
    timestamp: string;
}

interface AdminDashboardProps {
    stats: {
        total_users: number;
        active_users_today: number;
        recent_registrations: number;
        users_by_role: Record<string, number>;
        total_roles: number;
        role_assignments_today: number;
        role_removals_today: number;
        audit_logs_this_week: number;
        audit_logs_today: number;
        most_active_admin: {
            name: string;
            activities: number;
        } | null;
        system_health_score: number;
        database_connections: {
            active: number;
            status: string;
        };
        cache_hit_rate: number;
        recent_activities: Activity[];
    };
    performanceMetrics: {
        response_time_avg: string;
        error_rate: string;
        uptime: string;
        memory_usage: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
];

export default function AdminDashboard({ stats }: AdminDashboardProps) {
    // BROADCASTING REMOVED: Real-time updates via Echo have been removed.
    // Stats are now static and refreshed via standard Inertia page reloads.
    // To re-enable real-time updates in the future:
    // 1. Configure broadcasting driver in .env (pusher, redis, etc.)
    // 2. Set up Laravel Echo
    // 3. Listen for 'stats.updated' events on the 'admin-stats' channel

    const recentActivities = stats.recent_activities || [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold">Admin Dashboard</h1>
                    <p className="text-muted-foreground">
                        System overview and administrative controls.
                    </p>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Users
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_users}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                +{stats.recent_registrations} this week
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Roles
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total_roles}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                System roles configured
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Active Sessions
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.active_users_today}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Users active today
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Role Assignments
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.role_assignments_today}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Today
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Audit Activity
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.audit_logs_this_week}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                This week
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                System Health
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                Healthy
                            </div>
                            <p className="text-xs text-muted-foreground">
                                All systems operational
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Activities */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {recentActivities.length === 0 ? (
                                <p className="py-4 text-center text-muted-foreground">
                                    No recent activity to display.
                                </p>
                            ) : (
                                recentActivities.map((activity: Activity) => (
                                    <div
                                        key={activity.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div className="flex items-center space-x-3">
                                            <div className="flex-shrink-0">
                                                <Badge
                                                    variant={
                                                        activity.action ===
                                                        'assigned'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {activity.action}
                                                </Badge>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {activity.admin_name}{' '}
                                                    {activity.action} "
                                                    {activity.role}" role
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Target:{' '}
                                                    {activity.target_name} •{' '}
                                                    {activity.timestamp}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Quick Actions */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card className="cursor-pointer transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="text-lg">
                                User Management
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.active_users_today}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Users active today
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="cursor-pointer transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="text-lg">
                                Audit Logs
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-muted-foreground">
                                Monitor all role changes and system activities.
                            </p>
                            <a
                                href="/admin/audit"
                                className="text-sm font-medium text-blue-600 hover:text-blue-800"
                            >
                                View Audit Logs →
                            </a>
                        </CardContent>
                    </Card>

                    <Card className="cursor-pointer transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="text-lg">
                                System Settings
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-4 text-sm text-muted-foreground">
                                Configure system-wide settings and preferences.
                            </p>
                            <span className="text-sm text-gray-400">
                                Coming soon
                            </span>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
