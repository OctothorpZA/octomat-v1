<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a role is removed from a user.
 *
 * BROADCASTING REMOVED: This event no longer broadcasts in real-time.
 * It is dispatched for audit logging purposes only.
 * To re-enable broadcasting in the future:
 * 1. Implement ShouldBroadcast interface
 * 2. Add broadcastOn(), broadcastAs(), broadcastWith() methods
 * 3. Configure broadcasting driver in .env (pusher, redis, etc.)
 * 4. Set up Laravel Echo on the frontend
 */
class RoleRemoved
{
    use Dispatchable, SerializesModels;

    public User $user;

    public string $role;

    public User $admin;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $role, User $admin)
    {
        $this->user = $user;
        $this->role = $role;
        $this->admin = $admin;
    }
}
