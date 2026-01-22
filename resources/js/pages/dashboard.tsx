import { Head, Link } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardProps {
    user: {
        id: number;
        first_name: string;
        full_name: string;
        email: string;
        created_at: string;
    };
    userRoles: string[];
    widgets: Array<{
        type:
            | 'stats'
            | 'actions'
            | 'profile'
            | 'performance'
            | 'family'
            | 'welcome';
        title: string;
        data?: Record<string, any>;
        actions?: Array<{ label: string; route: string }>;
        priority: number;
    }>;
}

interface WidgetProps {
    widget: {
        type:
            | 'stats'
            | 'actions'
            | 'profile'
            | 'performance'
            | 'family'
            | 'welcome';
        title: string;
        data?: Record<string, any>;
        actions?: Array<{ label: string; route: string }>;
        priority: number;
    };
}

function Widget({ widget }: WidgetProps) {
    const renderWidgetContent = () => {
        switch (widget.type) {
            case 'stats':
                return (
                    <div className="space-y-3">
                        {Object.entries(widget.data || {}).map(
                            ([key, value]) => (
                                <div
                                    key={key}
                                    className="flex items-center justify-between"
                                >
                                    <span className="text-sm text-muted-foreground capitalize">
                                        {key.replace(/_/g, ' ')}
                                    </span>
                                    <Badge variant="secondary">{value}</Badge>
                                </div>
                            ),
                        )}
                    </div>
                );

            case 'actions':
                return (
                    <div className="space-y-2">
                        {widget.actions?.map((action, index) => (
                            <Button
                                key={index}
                                variant="outline"
                                size="sm"
                                className="w-full justify-start"
                                asChild={action.route !== '#'}
                            >
                                {action.route === '#' ? (
                                    <span>{action.label}</span>
                                ) : (
                                    <Link href={action.route}>
                                        {action.label}
                                    </Link>
                                )}
                            </Button>
                        ))}
                    </div>
                );

            case 'profile':
                return (
                    <div className="space-y-2">
                        {Object.entries(widget.data || {}).map(
                            ([key, value]) => (
                                <div key={key} className="flex flex-col">
                                    <span className="text-sm text-muted-foreground capitalize">
                                        {key.replace(/_/g, ' ')}
                                    </span>
                                    <span className="font-medium">{value}</span>
                                </div>
                            ),
                        )}
                    </div>
                );

            case 'performance':
                return (
                    <div className="space-y-3">
                        {Object.entries(widget.data || {}).map(
                            ([key, value]) => (
                                <div key={key} className="flex justify-between">
                                    <span className="text-sm text-muted-foreground capitalize">
                                        {key.replace(/_/g, ' ')}
                                    </span>
                                    <Badge variant="outline">{value}</Badge>
                                </div>
                            ),
                        )}
                    </div>
                );

            case 'family':
                return (
                    <div className="space-y-3">
                        {Object.entries(widget.data || {}).map(
                            ([key, value]) => (
                                <div key={key} className="flex justify-between">
                                    <span className="text-sm text-muted-foreground capitalize">
                                        {key.replace(/_/g, ' ')}
                                    </span>
                                    <Badge variant="outline">{value}</Badge>
                                </div>
                            ),
                        )}
                    </div>
                );

            case 'welcome':
                return (
                    <div className="space-y-3">
                        <p className="text-sm">{widget.data?.message}</p>
                        {widget.data?.next_steps && (
                            <div className="space-y-1">
                                <p className="text-sm font-medium">
                                    Next steps:
                                </p>
                                <ul className="space-y-1 text-sm text-muted-foreground">
                                    {widget.data.next_steps.map(
                                        (step: string, index: number) => (
                                            <li
                                                key={index}
                                                className="flex items-center"
                                            >
                                                <span className="mr-2 h-2 w-2 rounded-full bg-primary"></span>
                                                {step}
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </div>
                        )}
                    </div>
                );

            default:
                return (
                    <div className="flex h-24 items-center justify-center">
                        <PlaceholderPattern className="h-8 w-8" />
                    </div>
                );
        }
    };

    return (
        <Card className="widget-transition">
            <CardHeader>
                <CardTitle className="text-lg">{widget.title}</CardTitle>
            </CardHeader>
            <CardContent>{renderWidgetContent()}</CardContent>
        </Card>
    );
}

export default function Dashboard({
    user,
    userRoles,
    widgets,
}: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="mx-auto max-w-6xl space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold">Dashboard</h1>
                    <p className="mt-2 text-muted-foreground">
                        Welcome back, {user.first_name}!
                    </p>
                </div>

                {/* Role Badges */}
                {userRoles.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                        {userRoles.map((role) => (
                            <Badge
                                key={role}
                                variant="default"
                                className="role-badge"
                            >
                                {role.replace(/_/g, ' ')}
                            </Badge>
                        ))}
                    </div>
                )}

                {/* Widgets Grid */}
                {widgets.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {widgets.map((widget, index) => (
                            <Widget
                                key={`widget-${widget.type}-${widget.title}-${index}`}
                                widget={widget}
                            />
                        ))}
                    </div>
                ) : (
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-center text-muted-foreground">
                                No widgets available for your current roles.
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Role-specific main content area (for future expansion) */}
                <Card className="mt-6">
                    <CardContent className="pt-6">
                        <p className="text-center text-muted-foreground">
                            Additional content based on your roles will appear
                            here in future updates.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
