<?php

namespace App\Http\Controllers\Admin;

use App\Events\RoleAssigned;
use App\Events\RoleRemoved;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleAssignRequest;
use App\Http\Requests\Admin\RoleRemoveRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleAssignmentController extends Controller
{
    /**
     * Display the user list with role assignment capabilities.
     */
    public function index(Request $request): \Inertia\Response
    {
        $query = User::with('roles')
            ->select('id', 'first_name', 'middle_names', 'last_name', 'email');

        // Robust database-agnostic search (First + Middle + Last)
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(first_name) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(middle_names) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw(
                        "LOWER(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_names, ''), ' ', COALESCE(last_name, ''))) LIKE LOWER(?)",
                        ["%{$search}%"]
                    )
                    ->orWhereRaw(
                        "LOWER(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) LIKE LOWER(?)",
                        ["%{$search}%"]
                    )
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%{$search}%"]);
            });
        }

        // Filter by specific role
        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->role));
        }

        return Inertia::render('admin/role-assignment', [
            'users' => $query->paginate(15)->withQueryString(),
            'roles' => $this->getAvailableRoles(),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * Assign a new role to a user.
     *
     * SIMPLIFIED - Hierarchy and conflict checks temporarily disabled.
     * Will re-enable when role hierarchy features are needed.
     *
     * @todo Re-enable hierarchy checks (lines marked with HIERARCHY-DISABLED)
     */
    public function assign(RoleAssignRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::findOrFail($validated['selectedUser']);
        $role = Role::where('name', $validated['selectedRole'])->firstOrFail();
        $currentUser = $request->user();

        // Security: Prevent self-assignment of administrative roles
        // This check is kept even without level system for security
        if ($currentUser->id === $user->id && $role->name !== 'General User') {
            return back()->withErrors(['authorization' => 'Security: Cannot assign administrative roles to yourself.']);
        }

        // HIERARCHY-DISABLED: Level-based authorization checks
        // if ($currentUser->getHighestRoleLevel() <= $role->level && ! $currentUser->hasRole('Super Admin')) {
        //     return back()->withErrors(['authorization' => 'Access Denied: Cannot assign roles at or above your own authority level.']);
        // }

        // HIERARCHY-DISABLED: Role conflict detection based on levels
        // $conflicting = $user->roles->filter(function ($r) use ($role) {
        //     if (abs($r->level - $role->level) >= 50) {
        //         return false;
        //     }
        //     if ($r->name === $role->name) {
        //         return false;
        //     }
        //     ... whitelist logic ...
        //     return true;
        // });
        // if ($conflicting->isNotEmpty()) { ... }

        // Validation: Prevent duplicate assignment
        if ($user->hasRole($role->name)) {
            return back()->withErrors(['role' => 'This user already holds the selected role.']);
        }

        // Execution
        $user->assignRole($role);

        // Audit Trail
        app(AuditService::class)->logRoleChange(
            $currentUser,
            $user,
            'assigned',
            $role->name
        );

        // Real-time Event
        RoleAssigned::dispatch($user, $role->name, $currentUser);

        return back()->with('success', "Role '{$role->display_name}' assigned to {$user->first_name} successfully.");
    }

    /**
     * Remove a role from a user.
     */
    public function remove(RoleRemoveRequest $request): RedirectResponse
    {
        $validated = $request->validate([
            'selectedUser' => 'required|exists:users,id',
            'selectedRole' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($validated['selectedUser']);
        $roleName = $validated['selectedRole'];

        if (! $user->hasRole($roleName)) {
            return back()->withErrors(['role' => 'User does not possess this role.']);
        }

        $user->removeRole($roleName);

        app(AuditService::class)->logRoleChange(
            $request->user(),
            $user,
            'removed',
            $roleName
        );

        RoleRemoved::dispatch($user, $roleName, $request->user());

        return back()->with('success', 'Role removed successfully.');
    }

    /**
     * Display role change audit logs.
     */
    public function audit(Request $request): \Inertia\Response
    {
        $page = $request->integer('page', 1);

        return Inertia::render('admin/audit-log', [
            'auditLogs' => app(AuditService::class)->getAuditLogs($page),
            'stats' => app(AuditService::class)->getAuditStats(),
        ]);
    }

    /**
     * Dynamically fetch roles from the database.
     *
     * SIMPLIFIED - Using alphabetical ordering instead of level-based.
     * Will re-enable level ordering when hierarchy is needed.
     *
     * @todo Re-enable: ->orderBy('level', 'desc')
     */
    private function getAvailableRoles(): array
    {
        return Role::orderBy('name', 'asc')  // HIERARCHY-DISABLED: Changed from orderBy('level', 'desc')
            ->get()
            ->mapWithKeys(function ($role) {
                $display = $role->display_name ?: str_replace(['-', '_'], ' ', ucwords($role->name, '-_'));

                return [$role->name => $display];
            })
            ->toArray();
    }
}
