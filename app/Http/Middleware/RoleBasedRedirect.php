<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Allow unauthenticated requests to pass through
        if (! $user) {
            return $next($request);
        }

        // Advanced role-based redirects
        $redirectResponse = $this->handleRoleBasedRedirects($request, $user);
        if ($redirectResponse) {
            return $redirectResponse;
        }

        // Handle multi-role conflicts
        $conflictResponse = $this->handleMultiRoleConflicts($request, $user);
        if ($conflictResponse) {
            return $conflictResponse;
        }

        // Check authorization levels for admin routes
        $authResponse = $this->handleAuthorizationChecks($request, $user);
        if ($authResponse) {
            return $authResponse;
        }

        return $next($request);
    }

    /**
     * Handle role-based redirects for authenticated users.
     */
    private function handleRoleBasedRedirects(Request $request, $user): ?Response
    {
        // Super Admin redirects - disabled to allow unified dashboard
        // if ($user->hasRole('Super Admin')) {
        //     // Redirect to admin dashboard if accessing general dashboard
        //     if ($request->is('/') || $request->is('dashboard')) {
        //         return redirect()->route('admin.dashboard');
        //     }
        // }

        // Academy Owner redirects
        if ($user->hasRole('Academy Owner') && ! $user->hasAnyRole(['Super Admin', 'System Administrator'])) {
            if ($request->is('dashboard') || $request->is('/')) {
                // For MVP, redirect to main dashboard with academy-specific messaging
                return redirect()->route('dashboard')
                    ->with('info', 'Welcome to your Academy Dashboard. Advanced academy features coming soon.');
            }

            // Prevent academy owners from accessing admin areas
            if ($request->is('admin/*')) {
                // Log unauthorized access attempt
                app(AuditService::class)->logAccessAttempt($user, $request->path(), 'Unauthorized admin access attempt by Academy Owner');

                return redirect()->route('dashboard')
                    ->with('error', 'Access denied. Academy owners cannot access admin areas.');
            }
        }

        // Club Manager redirects
        if ($user->hasRole('Club Manager') && ! $user->hasAnyRole(['Super Admin', 'System Administrator', 'Academy Owner'])) {
            if ($request->is('dashboard') || $request->is('/')) {
                // For MVP, redirect to main dashboard with club-specific messaging
                return redirect()->route('dashboard')
                    ->with('info', 'Welcome to your Club Dashboard. Advanced club features coming soon.');
            }

            // Prevent club managers from accessing admin areas
            if ($request->is('admin/*')) {
                // Log unauthorized access attempt
                app(AuditService::class)->logAccessAttempt($user, $request->path(), 'Unauthorized admin access attempt by Club Manager');

                return redirect()->route('dashboard')
                    ->with('error', 'Access denied. Club managers cannot access admin areas.');
            }
        }

        return null;
    }

    /**
     * Handle users with multiple conflicting roles.
     */
    private function handleMultiRoleConflicts(Request $request, $user): ?Response
    {
        // TODO Sprint 5: Re-enable multi-role conflict handling with role selection UI
        // Disabled for MVP - allow multi-role assignments

        // $userRoles = $user->roles->pluck('name')->toArray();

        // Check for conflicting role combinations
        // $conflicts = $this->detectRoleConflicts($userRoles);

        // if (! empty($conflicts)) {
        // If accessing sensitive areas with conflicts, redirect to role selection
        //    if ($request->is('admin/*') || $request->is('academy/*') || $request->is('club/*')) {
        //        return redirect()->route('dashboard')
        //            ->with('warning', 'You have multiple roles. Please select your primary context.')
        //            ->with('role_conflicts', $conflicts);
        //    }
        // }

        return null;
    }

    /**
     * Handle authorization level checks for admin routes.
     */
    private function handleAuthorizationChecks(Request $request, $user): ?Response
    {
        // Skip checks for non-admin routes
        if (! $request->is('admin/*')) {
            return null;
        }

        // Get user's highest role level
        $userLevel = $this->getHighestRoleLevel($user);

        // Check if user has sufficient permissions for admin access
        // Only Super Admin can access admin areas for now
        if (! $user->can('assign-roles')) {
            // Log unauthorized access attempt
            app(AuditService::class)->logAccessAttempt($user, $request->path(), 'Unauthorized admin access attempt');
            abort(403, 'Access denied. Only Super Admin can access admin areas.');
        }

        // Additional level-based checks can be added here
        // For example, System Administrators might have limited admin access compared to Super Admins

        return null;
    }

    /**
     * Detect conflicting role combinations.
     */
    private function detectRoleConflicts(array $roles): array
    {
        $conflicts = [];

        // Define conflicting role groups
        $roleGroups = [
            'management' => ['Super Admin', 'System Administrator', 'Academy Owner', 'Club Manager'],
            'coaching' => ['Coach', 'Assistant Coach'],
            'parenting' => ['Parent/Guardian'],
            'participation' => ['Athlete', 'Event Staff'],
        ];

        foreach ($roleGroups as $groupName => $groupRoles) {
            $matchingRoles = array_intersect($roles, $groupRoles);
            if (count($matchingRoles) > 1) {
                $conflicts[] = [
                    'group' => $groupName,
                    'roles' => $matchingRoles,
                    'message' => "Multiple {$groupName} roles detected: ".implode(', ', $matchingRoles),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Get the highest role level for a user.
     */
    private function getHighestRoleLevel($user): int
    {
        $highestLevel = 0;

        // Define role levels matching the RoleSeeder (higher number = higher authority)
        $roleLevels = [
            'General User' => 100,
            'Athlete' => 200,
            'Event Staff' => 300,
            'Parent/Guardian' => 250,
            'Coach' => 400,
            'Club Admin' => 500,
            'Club Manager' => 600,
            'Academy Owner' => 700,
            'Affiliate Manager' => 750,
            'Event Organiser' => 800,
            'Federation Admin' => 900,
            'Super Admin' => 1000,
        ];

        foreach ($user->roles as $role) {
            $level = $roleLevels[$role->name] ?? 0;
            $highestLevel = max($highestLevel, $level);
        }

        return $highestLevel;
    }
}
