<?php

namespace App\Http\Controllers\Admin;

use App\Events\RoleAssigned;
use App\Events\RoleRemoved;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleAssignmentController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $query = User::with('roles')
            ->select('id', 'first_name', 'middle_names', 'last_name', 'email');

        // Add real-time search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Add role filtering
        if ($request->filled('role')) {
            $role = $request->role;
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        return Inertia::render('admin/role-assignment', [
            'users' => $query->paginate(15)->withQueryString(),
            'roles' => $this->getAvailableRoles(),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'selectedUser' => 'required|exists:users,id',
            'selectedRole' => 'required|string',
        ]);

        // Find user and role
        $user = User::find($validated['selectedUser']);
        $role = Role::where('name', $validated['selectedRole'])->first();

        if (! $role) {
            return back()->withErrors(['role' => 'Role not found']);
        }

        // Check role level hierarchy (assignee cannot assign higher-level roles)
        if ($request->user()->getHighestRoleLevel() <= $role->level && ! $request->user()->hasRole('Super Admin')) {
            return back()->withErrors(['authorization' => 'Cannot assign roles at or above your authority level']);
        }

        // Check for existing conflicting roles (prevent similar level assignments)
        $conflictingRoles = $user->roles->filter(function ($existingRole) use ($role) {
            return abs($existingRole->level - $role->level) < 100 && $existingRole->name !== $role->name;
        });

        if ($conflictingRoles->isNotEmpty()) {
            $conflictNames = $conflictingRoles->pluck('display_name')->join(', ');

            return back()->withErrors(['conflict' => "Cannot assign this role. User already has conflicting roles: {$conflictNames}"]);
        }

        // Check if user already has this role (prevent duplicates)
        if ($user->hasRole($validated['selectedRole'])) {
            return redirect()->back()->withErrors(['role' => 'User already has this role assigned.']);
        }

        // Execute role assignment (add role without removing existing ones)
        $user->assignRole($validated['selectedRole']);

        // Log the audit trail
        app(AuditService::class)->logRoleChange(
            $request->user(),
            $user,
            'assigned',
            $validated['selectedRole']
        );

        // Dispatch broadcast event for real-time updates
        RoleAssigned::dispatch($user, $validated['selectedRole'], $request->user());

        return redirect()->back()->with('success', 'Role assigned successfully!');
    }

    public function remove(Request $request)
    {
        $validated = $request->validate([
            'selectedUser' => 'required|exists:users,id',
            'selectedRole' => 'required|string',
        ]);

        // Find user
        $user = User::find($validated['selectedUser']);

        // Execute role removal
        $user->removeRole($validated['selectedRole']);

        // Log the audit trail
        app(AuditService::class)->logRoleChange(
            $request->user(),
            $user,
            'removed',
            $validated['selectedRole']
        );

        // Dispatch broadcast event for real-time updates
        RoleRemoved::dispatch($user, $validated['selectedRole'], $request->user());

        return redirect()->back()->with('success', 'Role removed successfully!');
    }

    public function audit(Request $request)
    {
        $auditLogs = app(AuditService::class)->getAuditLogs($request->get('page', 1));
        $stats = app(AuditService::class)->getAuditStats();

        return Inertia::render('admin/audit-log', [
            'auditLogs' => $auditLogs,
            'stats' => $stats,
        ]);
    }

    private function getAvailableRoles(): array
    {
        return [
            'Super Admin' => 'System Administrator',
            'Federation Admin' => 'Federation Administrator',
            'Event Organiser' => 'Event Organiser',
            'Affiliate Manager' => 'Affiliate Manager',
            'Academy Owner' => 'Academy Owner',
            'Club Manager' => 'Club Manager',
            'Club Admin' => 'Club Administrator',
            'Coach' => 'Coach',
            'Parent/Guardian' => 'Parent/Guardian',
            'Athlete' => 'Athlete',
            'Event Staff' => 'Event Staff',
            'General User' => 'General User',
        ];
    }
}
