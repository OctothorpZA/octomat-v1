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
        $performanceMetrics = $statsService->getPerformanceMetrics();

        return Inertia::render('admin/dashboard', [
            'stats' => $stats,
            'performanceMetrics' => $performanceMetrics,
        ]);
    }

    private function getWidgetsForRole(string $role, $user): array
    {
        return match ($role) {
            'Super Admin' => $this->getSuperAdminWidgets($user),
            'Coach' => $this->getCoachWidgets($user),
            'Athlete' => $this->getAthleteWidgets($user),
            'Parent/Guardian' => $this->getParentWidgets($user),
            'Academy Owner' => $this->getAcademyOwnerWidgets($user),
            'Club Manager' => $this->getClubManagerWidgets($user),
            'General User' => $this->getGeneralUserWidgets($user),
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

    private function getCoachWidgets($user): array
    {
        return [
            [
                'type' => 'actions',
                'title' => 'Coach Tools',
                'actions' => [
                    ['label' => 'My Athletes', 'route' => '#'], // Placeholder for future
                    ['label' => 'Training Programs', 'route' => '#'], // Placeholder for future
                    ['label' => 'Competition Results', 'route' => '#'], // Placeholder for future
                ],
                'priority' => 1,
            ],
            [
                'type' => 'stats',
                'title' => 'Coach Statistics',
                'data' => [
                    'active_athletes' => '—', // Placeholder for future
                    'upcoming_events' => '—', // Placeholder for future
                ],
                'priority' => 2,
            ],
        ];
    }

    private function getAthleteWidgets($user): array
    {
        return [
            [
                'type' => 'profile',
                'title' => 'My Profile',
                'data' => [
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'member_since' => $user->created_at->format('M Y'),
                ],
                'priority' => 1,
            ],
            [
                'type' => 'performance',
                'title' => 'Performance Stats',
                'data' => [
                    'recent_competitions' => '—', // Placeholder for future
                    'upcoming_events' => '—', // Placeholder for future
                    'training_streak' => '—', // Placeholder for future
                ],
                'priority' => 2,
            ],
            [
                'type' => 'actions',
                'title' => 'Quick Actions',
                'actions' => [
                    ['label' => 'View Results', 'route' => '#'], // Placeholder for future
                    ['label' => 'Training Log', 'route' => '#'], // Placeholder for future
                ],
                'priority' => 3,
            ],
        ];
    }

    private function getParentWidgets($user): array
    {
        return [
            [
                'type' => 'family',
                'title' => 'Family Overview',
                'data' => [
                    'linked_athletes' => '—', // Placeholder for future
                    'upcoming_events' => '—', // Placeholder for future
                ],
                'priority' => 1,
            ],
            [
                'type' => 'actions',
                'title' => 'Family Tools',
                'actions' => [
                    ['label' => 'Manage Athletes', 'route' => '#'], // Placeholder for future
                    ['label' => 'View Progress', 'route' => '#'], // Placeholder for future
                ],
                'priority' => 2,
            ],
        ];
    }

    private function getAcademyOwnerWidgets($user): array
    {
        return [
            [
                'type' => 'stats',
                'title' => 'Academy Overview',
                'data' => [
                    'total_members' => '—', // Placeholder for future
                    'active_programs' => '—', // Placeholder for future
                    'monthly_revenue' => '—', // Placeholder for future
                ],
                'priority' => 1,
            ],
            [
                'type' => 'actions',
                'title' => 'Academy Management',
                'actions' => [
                    ['label' => 'Member Management', 'route' => '#'], // Placeholder for future
                    ['label' => 'Program Setup', 'route' => '#'], // Placeholder for future
                    ['label' => 'Financial Reports', 'route' => '#'], // Placeholder for future
                ],
                'priority' => 2,
            ],
        ];
    }

    private function getClubManagerWidgets($user): array
    {
        return [
            [
                'type' => 'stats',
                'title' => 'Club Statistics',
                'data' => [
                    'member_count' => '—', // Placeholder for future
                    'event_count' => '—', // Placeholder for future
                    'facility_usage' => '—', // Placeholder for future
                ],
                'priority' => 1,
            ],
            [
                'type' => 'actions',
                'title' => 'Club Management',
                'actions' => [
                    ['label' => 'Event Planning', 'route' => '#'], // Placeholder for future
                    ['label' => 'Member Directory', 'route' => '#'], // Placeholder for future
                    ['label' => 'Facility Booking', 'route' => '#'], // Placeholder for future
                ],
                'priority' => 2,
            ],
        ];
    }

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
