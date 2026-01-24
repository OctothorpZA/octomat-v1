<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'admin_id',
        'admin_name',
        'target_user_id',
        'target_user_name',
        'action',
        'role',
        'ip_address',
        'user_agent',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    /**
     * Get the admin user who performed the action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the target user who was affected by the action.
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Scope for filtering by action type.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by admin user.
     */
    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope for filtering by target user.
     */
    public function scopeByTargetUser($query, int $targetUserId)
    {
        return $query->where('target_user_id', $targetUserId);
    }

    /**
     * Scope for recent entries.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }
}
