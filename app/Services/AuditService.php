<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Log a role change action.
     *
     * @param  User  $admin  The admin performing the action
     * @param  User  $target  The user being affected
     * @param  string  $action  The action performed ('assigned' or 'removed')
     * @param  string  $role  The role being assigned/removed
     */
    public function logRoleChange(User $admin, User $target, string $action, string $role): void
    {
        // Create database record
        AuditLog::create([
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'target_user_id' => $target->id,
            'target_user_name' => $target->name,
            'action' => $action,
            'role' => $role,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);

        // Also log to Laravel log for additional tracking
        Log::info("Role {$action}", [
            'admin' => $admin->only(['id', 'name', 'email']),
            'target' => $target->only(['id', 'name', 'email']),
            'role' => $role,
            'ip' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log an unauthorized access attempt.
     *
     * @param  User  $user  The user attempting access
     * @param  string  $path  The path they tried to access
     * @param  string  $reason  The reason for denial
     */
    public function logAccessAttempt(User $user, string $path, string $reason): void
    {
        // Create database record for access attempts
        AuditLog::create([
            'admin_id' => $user->id,
            'admin_name' => $user->name,
            'target_user_id' => null, // No target user for access attempts
            'target_user_name' => null,
            'action' => 'access_denied',
            'role' => $path,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);

        // Log to Laravel log as warning
        Log::warning("Unauthorized access attempt: {$reason}", [
            'user' => $user->only(['id', 'name', 'email']),
            'path' => $path,
            'ip' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get recent audit logs for a specific user.
     */
    public function getUserAuditLogs(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('target_user_id', $userId)
            ->orWhere('admin_id', $userId)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by action type.
     */
    public function getLogsByAction(string $action, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::byAction($action)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get paginated audit logs for admin viewer.
     */
    public function getAuditLogs(int $page = 1, int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return AuditLog::orderBy('timestamp', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get audit statistics.
     */
    public function getAuditStats(): array
    {
        return [
            'total_logs' => AuditLog::count(),
            'recent_logs' => AuditLog::recent(7)->count(),
            'role_assignments' => AuditLog::byAction('assigned')->count(),
            'role_removals' => AuditLog::byAction('removed')->count(),
            'unique_admins' => AuditLog::distinct('admin_id')->count(),
            'unique_targets' => AuditLog::distinct('target_user_id')->count(),
        ];
    }
}
