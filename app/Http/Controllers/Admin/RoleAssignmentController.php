<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')
            ->select('id', 'first_name', 'middle_names', 'last_name', 'email');

        // Add comprehensive search functionality (enhanced from Sprint 3)
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                // Case-insensitive search for individual name components
                $q->whereRaw('LOWER(first_name) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(middle_names) LIKE LOWER(?)', ["%{$search}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE LOWER(?)', ["%{$search}%"])
                  // Case-insensitive search for concatenated full name (first + middle + last)
                    ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_names, ''), ' ', COALESCE(last_name, ''))) LIKE LOWER(?)", ["%{$search}%"])
                  // Case-insensitive search for concatenated first + last (common display format)
                    ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) LIKE LOWER(?)", ["%{$search}%"])
                  // Case-insensitive search for email
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%{$search}%"]);
            });
        }

        return Inertia::render('admin/role-assignment', [
            'users' => $query->paginate(10)->withQueryString(),
            'roles' => $this->getAvailableRoles(),
            'filters' => $request->only(['search']),
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

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'selectedUser' => 'required|exists:users,id',
            'selectedRole' => 'required|string|exists:roles,name',
        ]);

        // Advanced validation rules
        $user = User::find($validated['selectedUser']);
        $role = Role::where('name', $validated['selectedRole'])->first();

        // Prevent self-assignment of high-level roles (Super Admin and above)
        if ($request->user()->id === $user->id && $role->level >= 900) {
            return back()->withErrors(['authorization' => 'Cannot assign high-level administrative roles to yourself']);
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

        // Execute role assignment
        $user->syncRoles([$validated['selectedRole']]);

        return redirect()->back()->with('success', 'Role assigned successfully!');
    }
}
