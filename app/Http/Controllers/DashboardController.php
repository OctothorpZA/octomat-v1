<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $userRoles = $user->roles->pluck('name')->toArray();

        // Aggregate widgets from ALL user roles (unified dashboard)
        $widgets = [];
        foreach ($userRoles as $role) {
            $widgets = array_merge($widgets, $this->getWidgetsForRole($role, $user));
        }

        // Sort by priority and limit to reasonable number
        $widgets = collect($widgets)
            ->sortBy('priority')
            ->take(9) // Max 9 widgets (3 rows of 3)
            ->values()
            ->all();

        return Inertia::render('dashboard', [
            'user' => $user,
            'userRoles' => $userRoles,
            'widgets' => $widgets,
        ]);
    }

    public function admin(Request $request): Response
    {
        $statsService = app(\App\Services\DashboardStatsService::class);

        $stats = $statsService->getAdminStats();

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Get widgets for a specific role.
     *
     * ACADEMY-FIRST: Only Super Admin and General User widgets are active.
     * Other role widgets are commented out until those features are built.
     */
    private function getWidgetsForRole(string $role, $user): array
    {
        return match ($role) {
            'Super Admin' => $this->getSuperAdminWidgets($user),
            'General User' => $this->getGeneralUserWidgets($user),
            // FUTURE: Enable when features are built
            // 'Academy Owner' => $this->getAcademyOwnerWidgets($user),
            // 'Coach' => $this->getCoachWidgets($user),
            // 'Athlete' => $this->getAthleteWidgets($user),
            // 'Parent/Guardian' => $this->getParentWidgets($user),
            // 'Club Manager' => $this->getClubManagerWidgets($user),
            default => []
        };
    }

    private function getSuperAdminWidgets($user): array
    {
        return [
            [
                'type' => 'stats',
                'title' => 'System Overview',
                'data' => [
                    'total_users' => User::count(),
                    'total_roles' => \Spatie\Permission\Models\Role::count(),
                    'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
                ],
                'priority' => 1,
            ],
            [
                'type' => 'actions',
                'title' => 'Admin Actions',
                'actions' => [
                    ['label' => 'Manage User Roles', 'route' => '/admin/roles/assign'],
                    ['label' => 'System Settings', 'route' => '#'], // Placeholder for future
                    ['label' => 'Audit Logs', 'route' => '#'], // Placeholder for Sprint 4
                ],
                'priority' => 2,
            ],
            [
                'type' => 'stats',
                'title' => 'Security Status',
                'data' => [
                    'active_sessions' => '—', // Placeholder for future
                    'failed_logins' => '—', // Placeholder for future
                ],
                'priority' => 3,
            ],
        ];
    }

    /**
     * Coach widgets - FUTURE: Enable when Coach features are built.
     */
    // private function getCoachWidgets($user): array
    // {
    //     return [
    //         [
    //             'type' => 'actions',
    //             'title' => 'Coach Tools',
    //             'actions' => [
    //                 ['label' => 'My Athletes', 'route' => '#'],
    //                 ['label' => 'Training Programs', 'route' => '#'],
    //                 ['label' => 'Competition Results', 'route' => '#'],
    //             ],
    //             'priority' => 1,
    //         ],
    //     ];
    // }

    /**
     * Athlete widgets - FUTURE: Enable when Athlete Portal features are built.
     */
    // private function getAthleteWidgets($user): array
    // {
    //     return [
    //         [
    //             'type' => 'profile',
    //             'title' => 'My Profile',
    //             'data' => [
    //                 'name' => $user->full_name,
    //                 'email' => $user->email,
    //                 'member_since' => $user->created_at->format('M Y'),
    //             ],
    //             'priority' => 1,
    //         ],
    //     ];
    // }

    /**
     * Parent widgets - FUTURE: Enable when Parent Portal features are built.
     */
    // private function getParentWidgets($user): array
    // {
    //     return [
    //         [
    //             'type' => 'family',
    //             'title' => 'Family Overview',
    //             'data' => [
    //                 'linked_athletes' => 0,
    //                 'upcoming_events' => 0,
    //             ],
    //             'priority' => 1,
    //         ],
    //     ];
    // }

    /**
     * Academy Owner widgets - FUTURE: Enable when Academy Management features are built.
     */
    // private function getAcademyOwnerWidgets($user): array
    // {
    //     return [
    //         [
    //             'type' => 'stats',
    //             'title' => 'Academy Overview',
    //             'data' => [
    //                 'total_members' => 0,
    //                 'active_programs' => 0,
    //             ],
    //             'priority' => 1,
    //         ],
    //     ];
    // }

    /**
     * Club Manager widgets - FUTURE: Enable when Club Management features are built.
     */
    // private function getClubManagerWidgets($user): array
    // {
    //     return [
    //         [
    //             'type' => 'stats',
    //             'title' => 'Club Statistics',
    //             'data' => [
    //                 'member_count' => 0,
    //                 'event_count' => 0,
    //             ],
    //             'priority' => 1,
    //         ],
    //     ];
    // }

    private function getGeneralUserWidgets($user): array
    {
        return [
            [
                'type' => 'welcome',
                'title' => 'Welcome to Octomat',
                'data' => [
                    'message' => 'Welcome! Complete your profile to unlock more features.',
                    'next_steps' => ['Update profile', 'Explore events', 'Join community'],
                ],
                'priority' => 10, // Lowest priority
            ],
            [
                'type' => 'actions',
                'title' => 'Getting Started',
                'actions' => [
                    ['label' => 'Complete Profile', 'route' => '/settings/profile'],
                    ['label' => 'Browse Events', 'route' => '#'], // Placeholder for future
                    ['label' => 'Find Clubs', 'route' => '#'], // Placeholder for future
                ],
                'priority' => 9,
            ],
        ];
    }
}
