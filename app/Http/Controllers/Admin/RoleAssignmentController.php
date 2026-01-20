<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleAssignmentController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/role-assignment', [
            'users' => User::select('id', 'first_name', 'last_name', 'email')
                ->get(),
            'roles' => [
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
            ],
        ]);
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'selectedUser' => 'required|exists:users,id',
            'selectedRole' => 'required|string',
        ]);

        // Add: Prevent privilege escalation
        if (!$request->user()->hasRole('Super Admin')) {
            return back()->withErrors(['authorization' => 'Insufficient permissions']);
        }

        $user = User::find($validated['selectedUser']);
        $user->syncRoles([$validated['selectedRole']]);

        return redirect()->back()->with('success', 'Role assigned successfully!');
    }
}
