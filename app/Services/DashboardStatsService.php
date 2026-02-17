<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Simplified dashboard statistics service.
 *
 * Only includes real, measurable metrics. Placeholder/fake metrics removed.
 * Add metrics back when you have real data sources for them.
 */
class DashboardStatsService
{
    /**
     * Get admin dashboard statistics.
     *
     * Returns only real metrics that can be measured from the database.
     */
    public function getAdminStats(): array
    {
        return [
            // User statistics - Real data
            'total_users' => User::count(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'users_by_role' => $this->getUsersByRole(),

            // Role statistics - Real data
            'total_roles' => \Spatie\Permission\Models\Role::count(),
            'role_assignments_today' => AuditLog::where('action', 'assigned')
                ->whereDate('created_at', today())->count(),
            'role_removals_today' => AuditLog::where('action', 'removed')
                ->whereDate('created_at', today())->count(),

            // Audit statistics - Real data
            'audit_logs_this_week' => AuditLog::where('created_at', '>=', now()->subDays(7))->count(),
            'audit_logs_today' => AuditLog::whereDate('created_at', today())->count(),
            'most_active_admin' => $this->getMostActiveAdmin(),

            // Recent activity - Real data
            'recent_activities' => $this->getRecentActivities(10),
        ];
    }

    /**
     * Get user dashboard statistics.
     *
     * Simplified to show only basic user info.
     * Add role-specific stats when you build those features.
     */
    public function getUserDashboardStats(User $user): array
    {
        return [
            'user_info' => [
                'name' => $user->full_name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('display_name')->toArray(),
                'member_since' => $user->created_at->format('M Y'),
            ],
        ];
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
     * Get recent activities for dashboard.
     */
    private function getRecentActivities(int $limit = 10): array
    {
        return AuditLog::latest()
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

    /*
     * FUTURE METRICS - Implement when you have real data sources:
     *
     * 'active_users_today' => Requires tracking user sessions or last activity
     * 'system_health_score' => Requires monitoring error rates, performance
     * 'database_connections' => Requires database monitoring
     * 'cache_hit_rate' => Requires cache monitoring
     * 'performance_metrics' => Requires application performance monitoring
     *
     * For now, these have been removed to avoid displaying fake/placeholder data.
     * Add them back when you implement proper monitoring and tracking.
     */
}
