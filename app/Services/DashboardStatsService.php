<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    /**
     * Get comprehensive admin statistics.
     */
    public function getAdminStats(): array
    {
        return [
            // User statistics
            'total_users' => User::count(),
            'active_users_today' => $this->getActiveUsersToday(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'users_by_role' => $this->getUsersByRole(),

            // Role statistics
            'total_roles' => \Spatie\Permission\Models\Role::count(),
            'role_assignments_today' => AuditLog::where('action', 'assigned')
                ->whereDate('created_at', today())->count(),
            'role_removals_today' => AuditLog::where('action', 'removed')
                ->whereDate('created_at', today())->count(),

            // Audit statistics
            'audit_logs_this_week' => AuditLog::where('created_at', '>=', now()->subDays(7))->count(),
            'audit_logs_today' => AuditLog::whereDate('created_at', today())->count(),
            'most_active_admin' => $this->getMostActiveAdmin(),

            // System health
            'system_health_score' => $this->calculateSystemHealthScore(),
            'database_connections' => $this->getDatabaseConnections(),
            'cache_hit_rate' => $this->getCacheHitRate(),

            // Recent activity
            'recent_activities' => $this->getRecentActivities(10),
        ];
    }

    /**
     * Get comprehensive user dashboard statistics.
     */
    public function getUserDashboardStats(User $user): array
    {
        $roleNames = $user->roles->pluck('name')->toArray();

        $stats = [
            'user_info' => [
                'name' => $user->full_name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('display_name')->toArray(),
                'member_since' => $user->created_at->format('M Y'),
                'last_login' => $user->last_login_at?->diffForHumans() ?? 'Never',
            ],
        ];

        // Add role-specific statistics
        foreach ($roleNames as $role) {
            $stats = array_merge($stats, $this->getRoleSpecificStats($user, $role));
        }

        return $stats;
    }

    /**
     * Get active users today (users who have logged in recently).
     */
    private function getActiveUsersToday(): int
    {
        // Since we don't have last_login_at, we'll use a different approach
        // Count users who have been active in the last 24 hours based on audit logs
        return AuditLog::where('created_at', '>=', now()->subDay())
            ->distinct('admin_id')
            ->count('admin_id');
    }

    /**
     * Get user count by role.
     */
    private function getUsersByRole(): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.display_name', DB::raw('count(*) as count'))
            ->groupBy('roles.id', 'roles.display_name')
            ->orderBy('count', 'desc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->display_name => $item->count];
            })
            ->toArray();
    }

    /**
     * Get the most active admin this week.
     */
    private function getMostActiveAdmin(): ?array
    {
        $result = AuditLog::select('admin_name', DB::raw('count(*) as activity_count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('admin_name')
            ->orderBy('activity_count', 'desc')
            ->first();

        return $result ? [
            'name' => $result->admin_name,
            'activities' => $result->activity_count,
        ] : null;
    }

    /**
     * Calculate system health score (0-100).
     */
    private function calculateSystemHealthScore(): int
    {
        $score = 100;

        // Check for recent errors (reduce score for errors)
        $recentErrors = AuditLog::where('created_at', '>=', now()->subHour())
            ->where('action', 'error')
            ->count();

        $score -= min($recentErrors * 5, 30); // Max 30 points deduction

        // Check user activity (reduce score for low activity)
        $activeUsers = $this->getActiveUsersToday();
        if ($activeUsers < 2) {
            $score -= 10;
        }

        // Check database performance (placeholder)
        $score = max(0, min(100, $score));

        return $score;
    }

    /**
     * Get database connection info.
     */
    private function getDatabaseConnections(): array
    {
        try {
            $connections = DB::select('SELECT count(*) as active FROM pg_stat_activity WHERE state = \'active\'');

            return [
                'active' => $connections[0]->active ?? 0,
                'status' => 'healthy',
            ];
        } catch (\Exception $e) {
            return [
                'active' => 0,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cache hit rate (placeholder - would need cache monitoring).
     */
    private function getCacheHitRate(): float
    {
        // Placeholder - in production you'd monitor actual cache metrics
        return 95.5; // 95.5% hit rate
    }

    /**
     * Get recent activities for dashboard.
     */
    private function getRecentActivities(int $limit = 10): array
    {
        return AuditLog::with(['admin', 'targetUser'])
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'role' => $log->role,
                    'admin_name' => $log->admin_name,
                    'target_name' => $log->target_user_name,
                    'timestamp' => $log->timestamp->toISOString(),
                    'time_ago' => $log->timestamp->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get role-specific statistics for user dashboard.
     */
    private function getRoleSpecificStats(User $user, string $role): array
    {
        return match ($role) {
            'Super Admin' => [
                'admin_stats' => [
                    'total_users_managed' => User::count(),
                    'recent_audits' => AuditLog::where('admin_id', $user->id)
                        ->where('created_at', '>=', now()->subWeek())
                        ->count(),
                ],
            ],
            'Coach' => [
                'coaching_stats' => [
                    'sessions_this_week' => 0, // Placeholder
                    'athletes_coached' => 0, // Placeholder
                ],
            ],
            'Athlete' => [
                'athlete_stats' => [
                    'upcoming_events' => 0, // Placeholder
                    'recent_results' => 0, // Placeholder
                ],
            ],
            'Parent/Guardian' => [
                'family_stats' => [
                    'linked_athletes' => 0, // Placeholder
                    'upcoming_events' => 0, // Placeholder
                ],
            ],
            default => []
        };
    }

    /**
     * Get system performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'response_time_avg' => '245ms', // Placeholder
            'error_rate' => '0.01%', // Placeholder
            'uptime' => '99.9%', // Placeholder
            'memory_usage' => '67%', // Placeholder
        ];
    }
}
