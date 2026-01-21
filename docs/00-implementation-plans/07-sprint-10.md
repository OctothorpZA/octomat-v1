# Sprint 7: Enterprise Authorization Intelligence

## Overview

Implement advanced enterprise authorization features for the Octomat platform, building upon the MVP and advanced systems from Sprints 2-6. This sprint adds ML-powered recommendations, comprehensive security monitoring, sophisticated federation support, and enterprise-grade analytics using patterns from the Octomat Redux prototype.

## Business Context

The Octomat platform requires enterprise authorization intelligence for:

- **ML-Powered Recommendations** - Automated permission optimization and security insights
- **Advanced Security Monitoring** - Suspicious activity detection and compliance automation
- **Federation Membership Model** - True multi-tenant permission management
- **Comprehensive Analytics** - Operational visibility and business intelligence
- **Access Level System** - Granular resource protection with type safety
- **Dynamic Role Optimization** - AI-driven role and permission refinement

## Implementation Strategy

### Phase-Based Approach

- **Phase 1**: ML Recommendation Engine (Permission optimization)
- **Phase 2**: Advanced Security Monitoring (Suspicious activity detection)
- **Phase 3**: Federation Membership System (Multi-tenant permissions)
- **Phase 4**: Enterprise Analytics (Comprehensive reporting)
- **Phase 5**: Access Level System (Type-safe resource protection)

### Key Decisions

#### 1. ML Recommendation Architecture

- **Decision**: Implement peer analysis and behavior pattern recognition
- **Rationale**: Automated permission optimization reduces manual administration
- **Implementation**: ML service with configurable recommendation algorithms

#### 2. Security Monitoring Strategy

- **Decision**: Risk-based suspicious activity detection with automated alerting
- **Rationale**: Proactive security and compliance automation
- **Implementation**: Threshold-based monitoring with escalation workflows

#### 3. Federation Membership Model

- **Decision**: JSON-based permissions within federation memberships
- **Rationale**: Flexible multi-tenant permission management
- **Implementation**: FederationMembership model with dynamic permission assignment

#### 4. Access Level System Design

- **Decision**: Enum-based access levels with authentication requirements
- **Rationale**: Type-safe, maintainable resource protection
- **Implementation**: AccessLevel enum with authentication logic

## Phase 1: ML Recommendation Engine

### 1. ML Permission Recommendation Service

```php
// app/Services/MLPermissionRecommendationService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class MLPermissionRecommendationService
{
    public function generateRecommendations(User $user): array
    {
        $cacheKey = "ml_recommendations_{$user->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            return [
                'peer_analysis' => $this->analyzePeerUsage($user),
                'behavior_patterns' => $this->analyzeBehaviorPatterns($user),
                'security_assessment' => $this->assessSecurityRisks($user),
                'optimization_suggestions' => $this->generateOptimizationSuggestions($user),
                'generated_at' => now(),
            ];
        });
    }

    protected function analyzePeerUsage(User $user): array
    {
        // Find users with similar roles and analyze permission usage patterns
        $peerUsers = User::whereHas('roles', function ($query) use ($user) {
            $query->whereIn('name', $user->getRoleNames());
        })->where('id', '!=', $user->id)->get();

        $peerPermissions = $peerUsers->flatMap(function ($peer) {
            return $peer->getAllPermissions();
        })->groupBy('name')->map->count()->sortDesc();

        $userPermissions = $user->getAllPermissions()->pluck('name');

        $recommendations = [];
        foreach ($peerPermissions as $permission => $count) {
            if (!$userPermissions->contains($permission) && $count > 2) {
                $recommendations[] = [
                    'permission' => $permission,
                    'confidence' => min($count / $peerUsers->count() * 100, 95),
                    'reason' => 'Frequently used by peers with similar roles',
                ];
            }
        }

        return [
            'peer_count' => $peerUsers->count(),
            'recommendations' => $recommendations,
            'most_common_permissions' => $peerPermissions->take(10)->toArray(),
        ];
    }

    protected function analyzeBehaviorPatterns(User $user): array
    {
        // Analyze user's permission usage patterns over time
        $auditLogs = RoleAssignmentAudit::where('target_user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->orderBy('created_at')
            ->get();

        $patterns = [
            'frequently_granted' => [],
            'rarely_used' => [],
            'recent_changes' => $auditLogs->where('created_at', '>=', now()->subDays(7)),
        ];

        // Analyze permission usage frequency
        $permissionUsage = $auditLogs->groupBy('role_name')
            ->map(function ($logs, $roleName) {
                return [
                    'grants' => $logs->where('action', 'granted')->count(),
                    'revocations' => $logs->where('action', 'revoked')->count(),
                    'last_activity' => $logs->max('created_at'),
                ];
            });

        return [
            'usage_patterns' => $permissionUsage,
            'optimization_opportunities' => $this->identifyOptimizationOpportunities($permissionUsage),
        ];
    }

    protected function assessSecurityRisks(User $user): array
    {
        $risks = [];

        // Check for high-privilege roles without recent activity
        $highPrivilegeRoles = ['Super Admin', 'Federation Admin'];
        $userHighRoles = $user->roles->whereIn('name', $highPrivilegeRoles);

        foreach ($userHighRoles as $role) {
            $lastActivity = RoleAssignmentAudit::where('target_user_id', $user->id)
                ->where('role_name', $role->name)
                ->max('created_at');

            if ($lastActivity && $lastActivity->diffInDays(now()) > 30) {
                $risks[] = [
                    'type' => 'inactive_high_privilege',
                    'role' => $role->name,
                    'last_activity' => $lastActivity,
                    'recommendation' => 'Consider role review or temporary suspension',
                ];
            }
        }

        // Check for permission creep
        $permissionCount = $user->getAllPermissions()->count();
        $roleCount = $user->roles->count();

        if ($permissionCount > $roleCount * 10) {
            $risks[] = [
                'type' => 'permission_creep',
                'permission_count' => $permissionCount,
                'role_count' => $roleCount,
                'recommendation' => 'Review and consolidate permissions',
            ];
        }

        return $risks;
    }

    protected function generateOptimizationSuggestions(User $user): array
    {
        $suggestions = [];

        // Suggest role consolidation
        if ($user->roles->count() > 3) {
            $suggestions[] = [
                'type' => 'role_consolidation',
                'description' => 'Consider consolidating multiple roles into fewer, higher-level roles',
                'impact' => 'high',
            ];
        }

        // Suggest permission cleanup
        $unusedPermissions = $this->identifyUnusedPermissions($user);
        if ($unusedPermissions->isNotEmpty()) {
            $suggestions[] = [
                'type' => 'permission_cleanup',
                'description' => "Remove {$unusedPermissions->count()} unused permissions",
                'impact' => 'medium',
                'permissions' => $unusedPermissions->pluck('name'),
            ];
        }

        return $suggestions;
    }

    protected function identifyUnusedPermissions(User $user): Collection
    {
        // Identify permissions that haven't been used in recent activity
        $allPermissions = $user->getAllPermissions();
        $usedPermissions = RoleAssignmentAudit::where('target_user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->distinct('role_name')
            ->pluck('role_name');

        return $allPermissions->filter(function ($permission) use ($usedPermissions) {
            return !$usedPermissions->contains($permission->name);
        });
    }

    protected function identifyOptimizationOpportunities(Collection $permissionUsage): array
    {
        $opportunities = [];

        foreach ($permissionUsage as $roleName => $usage) {
            if ($usage['revocations'] > $usage['grants'] * 2) {
                $opportunities[] = [
                    'role' => $roleName,
                    'issue' => 'Frequently revoked - may indicate incorrect role assignment',
                    'suggestion' => 'Review role fit or create more specific roles',
                ];
            }
        }

        return $opportunities;
    }
}
```

### 2. Recommendation Controller

```php
// app/Http/Controllers/Admin/PermissionRecommendationController.php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MLPermissionRecommendationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermissionRecommendationController extends Controller
{
    public function __construct(
        protected MLPermissionRecommendationService $mlService
    ) {}

    public function index()
    {
        return Inertia::render('admin/permission-recommendations', [
            'recommendations' => $this->getAllUserRecommendations(),
            'summary' => $this->getRecommendationSummary(),
        ]);
    }

    public function show(User $user)
    {
        return Inertia::render('admin/user-recommendations', [
            'user' => $user->load('roles.permissions'),
            'recommendations' => $this->mlService->generateRecommendations($user),
        ]);
    }

    public function applyRecommendations(Request $request, User $user)
    {
        $validated = $request->validate([
            'recommendations' => 'required|array',
            'recommendations.*.type' => 'required|string',
            'recommendations.*.action' => 'required|string',
            'recommendations.*.permission' => 'nullable|string',
        ]);

        $applied = [];
        $skipped = [];

        foreach ($validated['recommendations'] as $recommendation) {
            try {
                match ($recommendation['action']) {
                    'grant_permission' => $this->applyPermissionGrant($user, $recommendation),
                    'revoke_permission' => $this->applyPermissionRevoke($user, $recommendation),
                    'consolidate_roles' => $this->applyRoleConsolidation($user, $recommendation),
                    default => throw new \InvalidArgumentException('Unknown action'),
                };

                $applied[] = $recommendation;
            } catch (\Exception $e) {
                $skipped[] = [
                    'recommendation' => $recommendation,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return back()->with([
            'success' => "Applied {$applied->count()} recommendations",
            'applied' => $applied,
            'skipped' => $skipped,
        ]);
    }

    protected function getAllUserRecommendations(): array
    {
        $users = User::with('roles')->get();

        return $users->map(function ($user) {
            $recommendations = $this->mlService->generateRecommendations($user);

            return [
                'user' => $user,
                'recommendations_count' => collect($recommendations)->flatten(1)->count(),
                'security_risks' => $recommendations['security_assessment'] ?? [],
                'optimizations' => $recommendations['optimization_suggestions'] ?? [],
            ];
        })->filter(function ($data) {
            return $data['recommendations_count'] > 0;
        })->toArray();
    }

    protected function getRecommendationSummary(): array
    {
        $users = User::all();
        $totalRecommendations = 0;
        $securityRisks = 0;
        $optimizations = 0;

        foreach ($users as $user) {
            $recs = $this->mlService->generateRecommendations($user);
            $totalRecommendations += collect($recs)->flatten(1)->count();
            $securityRisks += count($recs['security_assessment'] ?? []);
            $optimizations += count($recs['optimization_suggestions'] ?? []);
        }

        return [
            'total_users' => $users->count(),
            'total_recommendations' => $totalRecommendations,
            'security_risks' => $securityRisks,
            'optimizations' => $optimizations,
        ];
    }

    protected function applyPermissionGrant(User $user, array $recommendation): void
    {
        $user->givePermissionTo($recommendation['permission']);

        // Log the automated change
        RoleAssignmentAudit::create([
            'assigned_by' => auth()->id(),
            'target_user_id' => $user->id,
            'role_name' => $recommendation['permission'],
            'action' => 'granted',
            'reason' => 'ML recommendation applied',
            'metadata' => [
                'source' => 'ml_recommendation',
                'confidence' => $recommendation['confidence'] ?? null,
                'algorithm' => 'peer_analysis',
            ],
        ]);
    }

    protected function applyPermissionRevoke(User $user, array $recommendation): void
    {
        $user->revokePermissionTo($recommendation['permission']);

        RoleAssignmentAudit::create([
            'assigned_by' => auth()->id(),
            'target_user_id' => $user->id,
            'role_name' => $recommendation['permission'],
            'action' => 'revoked',
            'reason' => 'ML optimization applied',
            'metadata' => [
                'source' => 'ml_optimization',
                'optimization_type' => $recommendation['type'],
            ],
        ]);
    }

    protected function applyRoleConsolidation(User $user, array $recommendation): void
    {
        // Implementation for role consolidation logic
        // This would analyze current roles and suggest consolidation
    }
}
```

### 3. React Components for Recommendations

```tsx
// resources/js/pages/admin/permission-recommendations.tsx
import React from 'react';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export default function PermissionRecommendations({
    recommendations,
    summary,
}: {
    recommendations: any[];
    summary: any;
}) {
    return (
        <>
            <Head title="Permission Recommendations" />
            <div className="container mx-auto px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">
                    ML Permission Recommendations
                </h1>

                {/* Summary Cards */}
                <div className="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Total Users</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">
                                {summary.total_users}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Recommendations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-blue-600">
                                {summary.total_recommendations}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Security Risks</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-red-600">
                                {summary.security_risks}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Optimizations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-green-600">
                                {summary.optimizations}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recommendations Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>User Recommendations</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Recommendations</TableHead>
                                    <TableHead>Security Risks</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recommendations.map((item) => (
                                    <TableRow key={item.user.id}>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium">
                                                    {item.user.full_name}
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {item.user.email}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {item.recommendations_count}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {item.security_risks.length > 0 ? (
                                                <Badge variant="destructive">
                                                    {item.security_risks.length}
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    None
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <a
                                                    href={`/admin/users/${item.user.id}/recommendations`}
                                                >
                                                    View Details
                                                </a>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
```

---

## Phase 2: Advanced Security Monitoring

### 1. Security Enhancement Service

```php
// app/Services/SecurityEnhancementService.php
<?php

namespace App\Services;

use App\Models\RoleAssignmentAudit;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SecurityEnhancementService
{
    public function detectSuspiciousPermissionChanges(array $changes): array
    {
        $suspicious = [];
        $threshold = config('rbac.security.suspicious_activity_threshold', 7.5);

        foreach ($changes as $change) {
            $riskScore = $this->calculateRiskScore($change);

            if ($riskScore >= $threshold) {
                $suspicious[] = [
                    'change' => $change,
                    'risk_score' => $riskScore,
                    'risk_level' => $this->getRiskLevel($riskScore),
                    'recommendations' => $this->generateSecurityRecommendations($change),
                    'detected_at' => now(),
                ];
            }
        }

        // Log suspicious activities
        if (!empty($suspicious)) {
            Log::warning('Suspicious permission changes detected', [
                'suspicious_activities' => $suspicious,
                'total_changes' => count($changes),
            ]);

            $this->sendSecurityAlert($suspicious);
        }

        return $suspicious;
    }

    protected function calculateRiskScore(array $change): float
    {
        $score = 0;

        // Base score based on action type
        $score += match ($change['action'] ?? '') {
            'grant_super_admin' => 10.0,
            'grant_high_privilege' => 8.5,
            'bulk_grant' => 6.0,
            'revoke_security_role' => 4.0,
            default => 1.0,
        };

        // User risk factors
        if ($change['target_user'] ?? null) {
            $user = User::find($change['target_user']);
            if ($user) {
                // Recent account creation
                if ($user->created_at->diffInDays(now()) < 7) {
                    $score += 2.0;
                }

                // High privilege count
                $privilegeCount = $user->getAllPermissions()->count();
                if ($privilegeCount > 20) {
                    $score += 1.5;
                }
            }
        }

        // Time-based factors
        $now = now();
        $changeTime = $change['timestamp'] ?? $now;
        $hour = $changeTime->hour;

        // Unusual hours (2 AM - 5 AM)
        if ($hour >= 2 && $hour <= 5) {
            $score += 1.5;
        }

        // Weekend changes
        if ($changeTime->isWeekend()) {
            $score += 1.0;
        }

        // Recent similar changes
        $recentChanges = RoleAssignmentAudit::where('assigned_by', $change['assigned_by'] ?? null)
            ->where('action', $change['action'] ?? null)
            ->where('created_at', '>=', now()->subHours(1))
            ->count();

        if ($recentChanges > 5) {
            $score += 2.0;
        }

        // Permission creep detection
        if ($this->detectsPermissionCreep($change)) {
            $score += 3.0;
        }

        return min($score, 10.0); // Cap at 10.0
    }

    protected function getRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 9.0 => 'CRITICAL',
            $score >= 7.5 => 'HIGH',
            $score >= 5.0 => 'MEDIUM',
            $score >= 2.5 => 'LOW',
            default => 'MINIMAL',
        };
    }

    protected function generateSecurityRecommendations(array $change): array
    {
        $recommendations = [];

        if ($change['action'] === 'grant_super_admin') {
            $recommendations[] = 'Require dual authorization for super admin grants';
            $recommendations[] = 'Schedule immediate security review';
            $recommendations[] = 'Monitor target user activity for 30 days';
        }

        if ($this->detectsPermissionCreep($change)) {
            $recommendations[] = 'Review user permission set for consolidation';
            $recommendations[] = 'Consider role-based access instead of individual permissions';
        }

        if ($change['bulk_operation'] ?? false) {
            $recommendations[] = 'Verify bulk operation was intentional';
            $recommendations[] = 'Check for automation or script-based changes';
        }

        return $recommendations;
    }

    protected function detectsPermissionCreep(array $change): bool
    {
        if (!isset($change['target_user'])) {
            return false;
        }

        $user = User::find($change['target_user']);
        if (!$user) {
            return false;
        }

        $permissionCount = $user->getAllPermissions()->count();
        $roleCount = $user->roles->count();

        // Permission count significantly exceeds role count
        return $permissionCount > $roleCount * 5;
    }

    protected function sendSecurityAlert(array $suspicious): void
    {
        // Send notifications to security team
        $securityAdmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();

        foreach ($securityAdmins as $admin) {
            // Send security alert notification
            // Implementation would use Laravel Notifications
        }
    }

    public function getSecurityHealthMetrics(): array
    {
        $last30Days = now()->subDays(30);

        return [
            'suspicious_activities' => RoleAssignmentAudit::where('created_at', '>=', $last30Days)
                ->where('metadata->security_risk', '>', 5.0)
                ->count(),

            'bulk_operations' => RoleAssignmentAudit::where('created_at', '>=', $last30Days)
                ->where('metadata->bulk_operation', true)
                ->count(),

            'super_admin_changes' => RoleAssignmentAudit::where('created_at', '>=', $last30Days)
                ->where('role_name', 'Super Admin')
                ->count(),

            'average_risk_score' => RoleAssignmentAudit::where('created_at', '>=', $last30Days)
                ->whereNotNull('metadata->security_risk')
                ->avg('metadata->security_risk'),
        ];
    }
}
```

### 2. Security Dashboard

```tsx
// resources/js/pages/admin/security-dashboard.tsx
import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export default function SecurityDashboard({
    metrics,
    suspiciousActivities,
    recentChanges,
}: {
    metrics: any;
    suspiciousActivities: any[];
    recentChanges: any[];
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Security Dashboard" />
            <div className="container mx-auto px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold">
                    Authorization Security Dashboard
                </h1>

                {/* Security Metrics */}
                <div className="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Suspicious Activities</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-red-600">
                                {metrics.suspicious_activities}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Last 30 days
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Bulk Operations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-blue-600">
                                {metrics.bulk_operations}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Last 30 days
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Super Admin Changes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-orange-600">
                                {metrics.super_admin_changes}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Last 30 days
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Average Risk Score</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-purple-600">
                                {metrics.average_risk_score?.toFixed(1) ??
                                    '0.0'}
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Out of 10.0
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Suspicious Activities */}
                {suspiciousActivities.length > 0 && (
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="text-red-600">
                                ⚠️ Suspicious Activities Detected
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {suspiciousActivities.map((activity, index) => (
                                    <Alert
                                        key={index}
                                        className="border-red-200"
                                    >
                                        <AlertDescription>
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <div className="font-medium">
                                                        {activity.change.action}{' '}
                                                        -{' '}
                                                        {
                                                            activity.change
                                                                .role_name
                                                        }
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">
                                                        Risk Score:{' '}
                                                        {activity.risk_score} (
                                                        {activity.risk_level})
                                                    </div>
                                                    <div className="mt-2 text-sm">
                                                        <strong>
                                                            Recommendations:
                                                        </strong>
                                                        <ul className="mt-1 list-inside list-disc">
                                                            {activity.recommendations.map(
                                                                (
                                                                    rec: string,
                                                                    i: number,
                                                                ) => (
                                                                    <li key={i}>
                                                                        {rec}
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ul>
                                                    </div>
                                                </div>
                                                <Badge
                                                    variant={
                                                        activity.risk_level ===
                                                        'CRITICAL'
                                                            ? 'destructive'
                                                            : activity.risk_level ===
                                                                'HIGH'
                                                              ? 'secondary'
                                                              : 'outline'
                                                    }
                                                >
                                                    {activity.risk_level}
                                                </Badge>
                                            </div>
                                        </AlertDescription>
                                    </Alert>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Recent Changes */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Authorization Changes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Role/Permission</TableHead>
                                    <TableHead>Assigned By</TableHead>
                                    <TableHead>Risk Level</TableHead>
                                    <TableHead>Time</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentChanges.map((change) => (
                                    <TableRow key={change.id}>
                                        <TableCell>
                                            {change.target_user?.full_name}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    change.action === 'granted'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {change.action}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {change.role_name}
                                        </TableCell>
                                        <TableCell>
                                            {change.assigned_by_user?.full_name}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    change.risk_level ===
                                                    'CRITICAL'
                                                        ? 'destructive'
                                                        : change.risk_level ===
                                                            'HIGH'
                                                          ? 'secondary'
                                                          : 'outline'
                                                }
                                            >
                                                {change.risk_level}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {change.created_at_human}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
```

---

## Phase 3: Federation Membership System

### 1. Federation Membership Model

```php
// app/Models/FederationMembership.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FederationMembership extends Model
{
    protected $fillable = [
        'user_id',
        'federation_id',
        'role',
        'permissions',
        'is_active',
        'joined_at',
        'expires_at',
        'assigned_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function federation(): BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->update(['permissions' => $permissions]);
        }
    }

    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_diff($permissions, [$permission]);
        $this->update(['permissions' => $permissions]);
    }

    public function getFederationPermissions(): array
    {
        return [
            'role' => $this->role,
            'permissions' => $this->permissions ?? [],
            'can_manage_members' => $this->hasPermission('federation.members.manage'),
            'can_manage_events' => $this->hasPermission('federation.events.manage'),
            'can_view_reports' => $this->hasPermission('federation.reports.view'),
            'is_admin' => $this->role === 'admin',
        ];
    }
}
```

### 2. Federation Permission Service

```php
// app/Services/FederationPermissionService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Federation;
use App\Models\FederationMembership;
use Illuminate\Support\Facades\Cache;

class FederationPermissionService
{
    public function getUserFederationPermissions(int $userId, int $federationId): array
    {
        $cacheKey = "federation_permissions_{$userId}_{$federationId}";

        return Cache::remember($cacheKey, 1800, function () use ($userId, $federationId) {
            $membership = FederationMembership::where('user_id', $userId)
                ->where('federation_id', $federationId)
                ->active()
                ->first();

            if (!$membership) {
                return [
                    'is_member' => false,
                    'role' => null,
                    'permissions' => [],
                    'can_manage_members' => false,
                    'can_manage_events' => false,
                    'can_view_reports' => false,
                    'is_admin' => false,
                ];
            }

            return array_merge([
                'is_member' => true,
            ], $membership->getFederationPermissions());
        });
    }

    public function canUserAccessFederationResource(
        User $user,
        Federation $federation,
        string $resource,
        string $action = 'view'
    ): bool {
        $permissions = $this->getUserFederationPermissions($user->id, $federation->id);

        if (!$permissions['is_member']) {
            return false;
        }

        // Global federation permissions
        if ($permissions['is_admin']) {
            return true;
        }

        // Specific resource permissions
        $requiredPermission = "federation.{$resource}.{$action}";

        return $permissions['permissions'] &&
               in_array($requiredPermission, $permissions['permissions']);
    }

    public function assignFederationRole(
        User $user,
        Federation $federation,
        string $role,
        array $permissions = [],
        ?User $assignedBy = null
    ): FederationMembership {
        // Remove existing membership
        FederationMembership::where('user_id', $user->id)
            ->where('federation_id', $federation->id)
            ->delete();

        // Create new membership
        return FederationMembership::create([
            'user_id' => $user->id,
            'federation_id' => $federation->id,
            'role' => $role,
            'permissions' => $permissions,
            'is_active' => true,
            'joined_at' => now(),
            'assigned_by' => $assignedBy?->id,
        ]);
    }

    public function updateFederationPermissions(
        User $user,
        Federation $federation,
        array $permissions
    ): bool {
        $membership = FederationMembership::where('user_id', $user->id)
            ->where('federation_id', $federation->id)
            ->active()
            ->first();

        if (!$membership) {
            return false;
        }

        $membership->update(['permissions' => $permissions]);
        $this->clearUserFederationCache($user->id, $federation->id);

        return true;
    }

    public function removeFederationMembership(User $user, Federation $federation): bool
    {
        $deleted = FederationMembership::where('user_id', $user->id)
            ->where('federation_id', $federation->id)
            ->delete();

        if ($deleted > 0) {
            $this->clearUserFederationCache($user->id, $federation->id);
        }

        return $deleted > 0;
    }

    protected function clearUserFederationCache(int $userId, int $federationId): void
    {
        $cacheKey = "federation_permissions_{$userId}_{$federationId}";
        Cache::forget($cacheKey);
    }

    public function getFederationMembers(Federation $federation): Collection
    {
        return FederationMembership::where('federation_id', $federation->id)
            ->active()
            ->with(['user', 'assignedBy'])
            ->get()
            ->map(function ($membership) {
                return [
                    'membership' => $membership,
                    'user' => $membership->user,
                    'permissions' => $membership->getFederationPermissions(),
                    'assigned_by' => $membership->assignedBy,
                    'joined_at' => $membership->joined_at,
                ];
            });
    }

    public function validateFederationPermissions(array $permissions): array
    {
        $validPermissions = [
            'federation.members.manage',
            'federation.members.view',
            'federation.events.manage',
            'federation.events.create',
            'federation.events.view',
            'federation.reports.view',
            'federation.reports.export',
            'federation.settings.manage',
        ];

        $invalid = array_diff($permissions, $validPermissions);

        return [
            'valid' => array_intersect($permissions, $validPermissions),
            'invalid' => $invalid,
            'is_valid' => empty($invalid),
        ];
    }
}
```

### 3. Database Migration

```php
// database/migrations/xxxx_xx_xx_create_federation_memberships_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federation_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('federation_id')->constrained()->onDelete('cascade');
            $table->string('role'); // admin, member, coach, etc.
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['user_id', 'federation_id']);
            $table->index(['federation_id', 'role']);
            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federation_memberships');
    }
};
```

---

## Phase 4: Enterprise Analytics

### 1. RBAC Analytics Service

```php
// app/Services/RbacAnalyticsService.php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\RoleAssignmentAudit;
use App\Models\FederationMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RbacAnalyticsService
{
    public function getRoleDistribution(): array
    {
        $roleCounts = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->orderByDesc('count')
            ->get();

        return [
            'distribution' => $roleCounts,
            'total_users' => User::count(),
            'total_assignments' => $roleCounts->sum('count'),
            'most_common_role' => $roleCounts->first(),
            'least_common_role' => $roleCounts->last(),
        ];
    }

    public function getPermissionUsage(): array
    {
        $permissionCounts = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->select('permissions.name', DB::raw('count(*) as role_count'))
            ->groupBy('permissions.name')
            ->orderByDesc('role_count')
            ->get();

        $userPermissionCounts = DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->select('permissions.name', DB::raw('count(*) as user_count'))
            ->groupBy('permissions.name')
            ->orderByDesc('user_count')
            ->get();

        return [
            'by_roles' => $permissionCounts,
            'by_users' => $userPermissionCounts,
            'most_used_permission' => $permissionCounts->first(),
            'least_used_permission' => $permissionCounts->last(),
        ];
    }

    public function getSecurityHealthMetrics(): array
    {
        $last30Days = now()->subDays(30);

        $suspiciousActivities = RoleAssignmentAudit::where('created_at', '>=', $last30Days)
            ->where('metadata->security_risk', '>', 5.0)
            ->count();

        $bulkOperations = RoleAssignmentAudit::where('created_at', '>=', $last30Days)
            ->where('metadata->bulk_operation', true)
            ->count();

        $superAdminChanges = RoleAssignmentAudit::where('created_at', '>=', $last30Days)
            ->where('role_name', 'Super Admin')
            ->count();

        $averageRiskScore = RoleAssignmentAudit::where('created_at', '>=', $last30Days)
            ->whereNotNull('metadata->security_risk')
            ->avg('metadata->security_risk');

        $federationMemberships = FederationMembership::active()->count();
        $expiredMemberships = FederationMembership::where('expires_at', '<=', now())
            ->where('is_active', true)
            ->count();

        return [
            'period' => 'last_30_days',
            'suspicious_activities' => $suspiciousActivities,
            'bulk_operations' => $bulkOperations,
            'super_admin_changes' => $superAdminChanges,
            'average_risk_score' => round($averageRiskScore ?? 0, 2),
            'federation_memberships' => $federationMemberships,
            'expired_memberships' => $expiredMemberships,
            'membership_health_score' => $federationMemberships > 0 ?
                round(($federationMemberships - $expiredMemberships) / $federationMemberships * 100, 1) : 100,
        ];
    }

    public function getUserBehaviorAnalysis(): array
    {
        $users = User::with('roles.permissions')->get();

        $analysis = $users->map(function ($user) {
            $roleCount = $user->roles->count();
            $permissionCount = $user->getAllPermissions()->count();
            $lastActivity = RoleAssignmentAudit::where('target_user_id', $user->id)
                ->max('created_at');

            $activityScore = $lastActivity ?
                max(0, 100 - $lastActivity->diffInDays(now()) * 2) : 0;

            return [
                'user' => $user,
                'role_count' => $roleCount,
                'permission_count' => $permissionCount,
                'permission_per_role_ratio' => $roleCount > 0 ? round($permissionCount / $roleCount, 1) : 0,
                'activity_score' => $activityScore,
                'last_activity' => $lastActivity,
                'risk_profile' => $this->calculateUserRiskProfile($user),
            ];
        });

        return [
            'user_analysis' => $analysis,
            'high_risk_users' => $analysis->filter(fn($u) => $u['risk_profile']['level'] === 'high'),
            'inactive_users' => $analysis->filter(fn($u) => $u['activity_score'] < 20),
            'over_privileged_users' => $analysis->filter(fn($u) => $u['permission_per_role_ratio'] > 5),
        ];
    }

    protected function calculateUserRiskProfile(User $user): array
    {
        $score = 0;
        $factors = [];

        // High privilege roles
        if ($user->hasRole(['Super Admin', 'Federation Admin'])) {
            $score += 3;
            $factors[] = 'high_privilege_role';
        }

        // Many permissions
        if ($user->getAllPermissions()->count() > 25) {
            $score += 2;
            $factors[] = 'excessive_permissions';
        }

        // Recently created account with high privileges
        if ($user->created_at->diffInDays(now()) < 30 &&
            $user->hasRole(['Super Admin', 'Federation Admin'])) {
            $score += 2;
            $factors[] = 'new_account_high_privilege';
        }

        // Low activity with high privileges
        $lastActivity = RoleAssignmentAudit::where('target_user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->exists();

        if (!$lastActivity && $user->hasRole(['Super Admin', 'Federation Admin'])) {
            $score += 1;
            $factors[] = 'inactive_high_privilege';
        }

        $level = match (true) {
            $score >= 5 => 'high',
            $score >= 3 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'factors' => $factors,
        ];
    }

    public function exportAnalytics(string $format = 'json'): string
    {
        $data = [
            'generated_at' => now(),
            'role_distribution' => $this->getRoleDistribution(),
            'permission_usage' => $this->getPermissionUsage(),
            'security_metrics' => $this->getSecurityHealthMetrics(),
            'user_behavior' => $this->getUserBehaviorAnalysis(),
        ];

        return match ($format) {
            'csv' => $this->exportToCsv($data),
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            default => json_encode($data),
        };
    }

    protected function exportToCsv(array $data): string
    {
        // Convert complex analytics data to CSV format
        // Implementation would flatten nested arrays and create CSV structure
        return "CSV export not yet implemented";
    }
}
```

### 2. Analytics Dashboard

```tsx
// resources/js/pages/admin/rbac-analytics.tsx
import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
} from 'recharts';

export default function RbacAnalytics({
    roleDistribution,
    permissionUsage,
    securityMetrics,
    userBehavior,
}: {
    roleDistribution: any;
    permissionUsage: any;
    securityMetrics: any;
    userBehavior: any;
}) {
    const [exportFormat, setExportFormat] = useState('json');

    const handleExport = () => {
        // Trigger analytics export
        window.open(`/admin/analytics/export?format=${exportFormat}`, '_blank');
    };

    return (
        <>
            <Head title="RBAC Analytics" />
            <div className="container mx-auto px-4 py-8">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">
                        RBAC Analytics & Reporting
                    </h1>
                    <div className="flex gap-2">
                        <select
                            value={exportFormat}
                            onChange={(e) => setExportFormat(e.target.value)}
                            className="rounded border px-3 py-2"
                        >
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                        <Button onClick={handleExport}>Export Data</Button>
                    </div>
                </div>

                <Tabs defaultValue="overview">
                    <TabsList className="mb-6">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="roles">
                            Role Distribution
                        </TabsTrigger>
                        <TabsTrigger value="permissions">
                            Permission Usage
                        </TabsTrigger>
                        <TabsTrigger value="security">
                            Security Health
                        </TabsTrigger>
                        <TabsTrigger value="users">User Analysis</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Total Users</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold">
                                        {roleDistribution.total_users}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Active Roles</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold">
                                        {roleDistribution.distribution.length}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Security Health</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-green-600">
                                        {
                                            securityMetrics.membership_health_score
                                        }
                                        %
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Healthy
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>High Risk Users</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-red-600">
                                        {userBehavior.high_risk_users.length}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="roles">
                        <Card>
                            <CardHeader>
                                <CardTitle>Role Distribution</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={400}>
                                    <BarChart
                                        data={roleDistribution.distribution}
                                    >
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="name" />
                                        <YAxis />
                                        <Tooltip />
                                        <Bar dataKey="count" fill="#8884d8" />
                                    </BarChart>
                                </ResponsiveContainer>

                                <div className="mt-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 className="font-semibold">
                                            Most Common Role
                                        </h4>
                                        <p>
                                            {
                                                roleDistribution
                                                    .most_common_role?.name
                                            }{' '}
                                            (
                                            {
                                                roleDistribution
                                                    .most_common_role?.count
                                            }{' '}
                                            users)
                                        </p>
                                    </div>
                                    <div>
                                        <h4 className="font-semibold">
                                            Total Assignments
                                        </h4>
                                        <p>
                                            {roleDistribution.total_assignments}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="permissions">
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Permissions by Roles</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer
                                        width="100%"
                                        height={300}
                                    >
                                        <BarChart
                                            data={permissionUsage.by_roles.slice(
                                                0,
                                                10,
                                            )}
                                        >
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis
                                                dataKey="name"
                                                angle={-45}
                                                textAnchor="end"
                                                height={80}
                                            />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar
                                                dataKey="role_count"
                                                fill="#82ca9d"
                                            />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Permissions by Users</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ResponsiveContainer
                                        width="100%"
                                        height={300}
                                    >
                                        <BarChart
                                            data={permissionUsage.by_users.slice(
                                                0,
                                                10,
                                            )}
                                        >
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis
                                                dataKey="name"
                                                angle={-45}
                                                textAnchor="end"
                                                height={80}
                                            />
                                            <YAxis />
                                            <Tooltip />
                                            <Bar
                                                dataKey="user_count"
                                                fill="#ffc658"
                                            />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="security">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Suspicious Activities</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-red-600">
                                        {securityMetrics.suspicious_activities}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Last 30 days
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Bulk Operations</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-blue-600">
                                        {securityMetrics.bulk_operations}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Last 30 days
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Average Risk</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-purple-600">
                                        {securityMetrics.average_risk_score}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Out of 10.0
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Membership Health</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold text-green-600">
                                        {
                                            securityMetrics.membership_health_score
                                        }
                                        %
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Active memberships
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="users">
                        <Card>
                            <CardHeader>
                                <CardTitle>User Behavior Analysis</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>User</TableHead>
                                            <TableHead>Roles</TableHead>
                                            <TableHead>Permissions</TableHead>
                                            <TableHead>Ratio</TableHead>
                                            <TableHead>Activity</TableHead>
                                            <TableHead>Risk</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {userBehavior.user_analysis
                                            .slice(0, 20)
                                            .map((analysis: any) => (
                                                <TableRow
                                                    key={analysis.user.id}
                                                >
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">
                                                                {
                                                                    analysis
                                                                        .user
                                                                        .full_name
                                                                }
                                                            </div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {
                                                                    analysis
                                                                        .user
                                                                        .email
                                                                }
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {analysis.role_count}
                                                    </TableCell>
                                                    <TableCell>
                                                        {
                                                            analysis.permission_count
                                                        }
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                analysis.permission_per_role_ratio >
                                                                5
                                                                    ? 'destructive'
                                                                    : analysis.permission_per_role_ratio >
                                                                        2
                                                                      ? 'secondary'
                                                                      : 'outline'
                                                            }
                                                        >
                                                            {
                                                                analysis.permission_per_role_ratio
                                                            }
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2">
                                                            <div
                                                                className={`h-2 w-2 rounded-full ${
                                                                    analysis.activity_score >
                                                                    70
                                                                        ? 'bg-green-500'
                                                                        : analysis.activity_score >
                                                                            30
                                                                          ? 'bg-yellow-500'
                                                                          : 'bg-red-500'
                                                                }`}
                                                            />
                                                            {
                                                                analysis.activity_score
                                                            }
                                                            %
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                analysis
                                                                    .risk_profile
                                                                    .level ===
                                                                'high'
                                                                    ? 'destructive'
                                                                    : analysis
                                                                            .risk_profile
                                                                            .level ===
                                                                        'medium'
                                                                      ? 'secondary'
                                                                      : 'outline'
                                                            }
                                                        >
                                                            {
                                                                analysis
                                                                    .risk_profile
                                                                    .level
                                                            }
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}
```

---

## Phase 5: Access Level System

### 1. Access Level Enum

```php
// app/Domain/Federation/ValueObjects/AccessLevel.php
<?php

namespace App\Domain\Federation\ValueObjects;

enum AccessLevel: string
{
    case PUBLIC = 'public';
    case MEMBER_ONLY = 'member_only';
    case ADMIN_ONLY = 'admin_only';

    public function requiresAuthentication(): bool
    {
        return match($this) {
            self::PUBLIC => false,
            self::MEMBER_ONLY, self::ADMIN_ONLY => true,
        };
    }

    public function getRequiredPermissions(): array
    {
        return match($this) {
            self::PUBLIC => [],
            self::MEMBER_ONLY => ['federation.access'],
            self::ADMIN_ONLY => ['federation.admin'],
        };
    }

    public function canAccess(User $user, Federation $federation = null): bool
    {
        return match($this) {
            self::PUBLIC => true,
            self::MEMBER_ONLY => $user && $federation &&
                app(FederationPermissionService::class)
                    ->canUserAccessFederationResource($user, $federation, 'access'),
            self::ADMIN_ONLY => $user && $federation &&
                app(FederationPermissionService::class)
                    ->canUserAccessFederationResource($user, $federation, 'admin'),
        };
    }

    public static function fromString(string $level): self
    {
        return match(strtolower($level)) {
            'public' => self::PUBLIC,
            'member_only', 'member-only' => self::MEMBER_ONLY,
            'admin_only', 'admin-only' => self::ADMIN_ONLY,
            default => throw new \InvalidArgumentException("Invalid access level: {$level}"),
        };
    }
}
```

### 2. Access Control Middleware

```php
// app/Http/Middleware/AccessLevelMiddleware.php
<?php

namespace App\Http\Middleware;

use App\Domain\Federation\ValueObjects\AccessLevel;
use App\Models\Federation;
use Closure;
use Illuminate\Http\Request;

class AccessLevelMiddleware
{
    public function handle(Request $request, Closure $next, string $level, ?int $federationId = null): mixed
    {
        $accessLevel = AccessLevel::fromString($level);
        $user = $request->user();
        $federation = $federationId ? Federation::find($federationId) : null;

        if (!$accessLevel->canAccess($user, $federation)) {
            if ($accessLevel->requiresAuthentication() && !$user) {
                return redirect()->guest(route('login'));
            }

            abort(403, 'Access denied');
        }

        return $next($request);
    }
}
```

---

## Success Criteria

### **ML-Powered Features**

- [ ] ML recommendation service analyzes user behavior patterns
- [ ] Peer analysis identifies frequently used permissions
- [ ] Security risk assessment prevents privilege escalation
- [ ] Optimization suggestions reduce permission maintenance
- [ ] Recommendation dashboard shows actionable insights

### **Advanced Security Monitoring**

- [ ] Suspicious activity detection with risk scoring
- [ ] Automated security alerts for high-risk changes
- [ ] Security health metrics dashboard
- [ ] Risk-based recommendations for security improvements
- [ ] Comprehensive audit trail with context

### **Federation Membership System**

- [ ] Federation-specific role assignments with JSON permissions
- [ ] Membership validation with expiry support
- [ ] Context-aware federation permission checking
- [ ] Flexible federation membership management
- [ ] Federation-specific audit trails

### **Enterprise Analytics**

- [ ] Comprehensive RBAC analytics dashboard
- [ ] Role distribution and permission usage statistics
- [ ] Security health metrics and user behavior analysis
- [ ] Export capabilities (JSON/CSV)
- [ ] Risk profile analysis for users

### **Access Level System**

- [ ] Type-safe access level enums with authentication requirements
- [ ] Access level validation middleware
- [ ] Resource protection based on access levels
- [ ] Integration with federation permission system

---

## Implementation Order (Week-by-Week Breakdown)

### **Week 1: ML Foundation**

- Implement ML recommendation service
- Create recommendation analytics
- Build recommendation dashboard UI

### **Week 2: Security Monitoring**

- Add security enhancement service
- Implement suspicious activity detection
- Create security dashboard

### **Week 3: Federation System**

- Create federation membership model
- Implement federation permission service
- Add federation membership management UI

### **Week 4: Analytics & Reporting**

- Build comprehensive analytics service
- Create analytics dashboard UI
- Add export functionality

### **Week 5: Access Levels & Polish**

- Implement access level system
- Add final middleware integrations
- Performance optimization and testing

---

## Files to Create/Modify

### New Models

- `app/Models/FederationMembership.php`
- `app/Domain/Federation/ValueObjects/AccessLevel.php`

### New Services

- `app/Services/MLPermissionRecommendationService.php`
- `app/Services/SecurityEnhancementService.php`
- `app/Services/FederationPermissionService.php`
- `app/Services/RbacAnalyticsService.php`

### New Controllers

- `app/Http/Controllers/Admin/PermissionRecommendationController.php`

### New Middleware

- `app/Http/Middleware/EnsureRoleAccess.php` (enhanced)
- `app/Http/Middleware/RestrictRouteByRole.php` (enhanced)
- `app/Http/Middleware/AccessLevelMiddleware.php`

### React Components

- `resources/js/pages/admin/permission-recommendations.tsx`
- `resources/js/pages/admin/security-dashboard.tsx`
- `resources/js/pages/admin/rbac-analytics.tsx`

### Database Migrations

- `database/migrations/xxxx_xx_xx_create_federation_memberships_table.php`
- `database/migrations/xxxx_xx_xx_add_access_levels_to_resources.php`

---

## Key Dependencies

- **Sprint 5 Completion**: MVP RBAC system must be working
- **Redis/Memcached**: For ML recommendation caching
- **Federation Data Model**: Federation table must exist
- **Audit System**: Role assignment audit table from Sprint 5
- **UI Component Library**: For advanced dashboard components

---

**Sprint Priority**: HIGH
**Estimated Effort**: 5 weeks (25 days)
**Dependencies**: Sprint 5 completion + federation data model
**Business Value**: Enterprise-grade security, operational intelligence, ML-driven optimization
**Technology Stack**: Laravel 12 + React 19 + Inertia.js v2 + Redis + ML algorithms
