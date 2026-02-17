<?php

namespace App\Services;

use App\Models\User;

class NavigationService
{
    /**
     * Get navigation for current authenticated user.
     */
    public static function getForCurrentUser(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        return app(static::class)->getNavigationForUser($user);
    }

    /**
     * Get navigation menu for a user based on their roles and permissions.
     */
    public function getNavigationForUser(User $user): array
    {
        $navigation = [
            [
                'title' => 'Dashboard',
                'href' => '/dashboard',
                'icon' => 'LayoutDashboard',
                'permission' => null,
                'priority' => 1,
            ],
        ];

        // Add role-specific navigation items
        foreach ($user->roles as $role) {
            $roleNavigation = $this->getNavigationForRole($role->name, $user);
            $navigation = array_merge($navigation, $roleNavigation);
        }

        // Sort by priority and remove duplicates
        $navigation = collect($navigation)
            ->sortBy('priority')
            ->unique('title')
            ->values()
            ->all();

        return $navigation;
    }

    /**
     * Get navigation items for a specific role.
     *
     * ACADEMY-FIRST: Only Super Admin and Academy Owner navigation is active.
     * Other roles are commented out until those features are built.
     */
    private function getNavigationForRole(string $roleName, User $user): array
    {
        return match ($roleName) {
            'Super Admin', 'System Administrator' => $this->getAdminNavigation($user),
            'Academy Owner' => $this->getAcademyNavigation($user),
            // FUTURE: Enable when features are built
            // 'Club Manager' => $this->getClubNavigation($user),
            // 'Coach' => $this->getCoachNavigation($user),
            // 'Athlete' => $this->getAthleteNavigation($user),
            // 'Parent/Guardian' => $this->getParentNavigation($user),
            default => []
        };
    }

    /**
     * Admin navigation items.
     */
    private function getAdminNavigation(User $user): array
    {
        return [
            [
                'title' => 'Admin Panel',
                'href' => '#',
                'icon' => 'Shield',
                'permission' => 'viewAdminDashboard',
                'priority' => 2,
                'children' => [
                    [
                        'title' => 'Dashboard',
                        'href' => '/admin/dashboard',
                        'icon' => 'BarChart3',
                        // grok old code  'permission' => 'viewAdminDashboard',
                        'permission' => 'system.admin', // claude change
                    ],
                    [
                        'title' => 'User Management',
                        'href' => '/admin/roles/assign',
                        'icon' => 'Users',
                        // grok old code  'permission' => 'manageRoles',
                        'permission' => 'assign-roles', // claude change
                    ],
                    [
                        'title' => 'Audit Logs',
                        'href' => '/admin/audit',
                        'icon' => 'FileText',
                        // grok old code  'permission' => 'viewAuditLog',
                        'permission' => 'system.audit', // claude change
                    ],
                    [
                        'title' => 'System Settings',
                        'href' => '#',
                        'icon' => 'Settings',
                        // grok old code 'permission' => 'manageSystem',
                        'permission' => 'system.admin', // claude change
                    ],
                ],
            ],
        ];
    }

    /**
     * Academy owner navigation items.
     */
    private function getAcademyNavigation(User $user): array
    {
        return [
            [
                'title' => 'Academy',
                'href' => '#',
                'icon' => 'Building',
                'permission' => 'manageAcademies',
                'priority' => 2,
                'children' => [
                    [
                        'title' => 'Dashboard',
                        'href' => '/dashboard',
                        'icon' => 'Home',
                        'permission' => null,
                    ],
                    [
                        'title' => 'Programs',
                        'href' => '#',
                        'icon' => 'BookOpen',
                        'permission' => 'manageAcademies',
                    ],
                    [
                        'title' => 'Coaches',
                        'href' => '#',
                        'icon' => 'UserCheck',
                        'permission' => 'manageAcademies',
                    ],
                    [
                        'title' => 'Athletes',
                        'href' => '#',
                        'icon' => 'Users',
                        'permission' => 'viewAthletes',
                    ],
                ],
            ],
        ];
    }

    /**
     * Club manager navigation items.
     *
     * FUTURE: Enable when Club Management features are built.
     */
    // private function getClubNavigation(User $user): array
    // {
    //     return [
    //         [
    //             'title' => 'Club',
    //             'href' => '#',
    //             'icon' => 'Target',
    //             'permission' => 'manageClubs',
    //             'priority' => 2,
    //             'children' => [
    //                 [
    //                     'title' => 'Dashboard',
    //                     'href' => '/dashboard',
    //                     'icon' => 'Home',
    //                     'permission' => null,
    //                 ],
    //                 [
    //                     'title' => 'Events',
    //                     'href' => '#',
    //                     'icon' => 'Calendar',
    //                     'permission' => 'manageClubs',
    //                 ],
    //                 [
    //                     'title' => 'Members',
    //                     'href' => '#',
    //                     'icon' => 'Users',
    //                     'permission' => 'manageClubs',
    //                 ],
    //                 [
    //                     'title' => 'Facilities',
    //                     'href' => '#',
    //                     'icon' => 'MapPin',
    //                     'permission' => 'manageClubs',
    //                 ],
    //             ],
    //         ],
    //     ];
    // }

    /**
     * Coach navigation items.
     *
     * FUTURE: Enable when Coach features are built.
     */
    // private function getCoachNavigation(User $user): array
    // {
    //     return [
    //         [
    //             'title' => 'Training',
    //             'href' => '#',
    //             'icon' => 'Dumbbell',
    //             'permission' => null,
    //             'priority' => 2,
    //             'children' => [
    //                 [
    //                     'title' => 'My Athletes',
    //                     'href' => '#',
    //                     'icon' => 'Users',
    //                     'permission' => 'viewAthletes',
    //                 ],
    //                 [
    //                     'title' => 'Programs',
    //                     'href' => '#',
    //                     'icon' => 'BookOpen',
    //                     'permission' => 'managePrograms',
    //                 ],
    //                 [
    //                     'title' => 'Sessions',
    //                     'href' => '#',
    //                     'icon' => 'Clock',
    //                     'permission' => 'manageSessions',
    //                 ],
    //             ],
    //         ],
    //     ];
    // }

    /**
     * Athlete navigation items.
     *
     * FUTURE: Enable when Athlete Portal features are built.
     */
    // private function getAthleteNavigation(User $user): array
    // {
    //     return [
    //         [
    //             'title' => 'My Sports',
    //             'href' => '#',
    //             'icon' => 'Trophy',
    //             'permission' => null,
    //             'priority' => 2,
    //             'children' => [
    //                 [
    //                     'title' => 'Results',
    //                     'href' => '#',
    //                     'icon' => 'TrendingUp',
    //                     'permission' => null,
    //                 ],
    //                 [
    //                     'title' => 'Training',
    //                     'href' => '#',
    //                     'icon' => 'Activity',
    //                     'permission' => null,
    //                 ],
    //                 [
    //                     'title' => 'Events',
    //                     'href' => '#',
    //                     'icon' => 'Calendar',
    //                     'permission' => null,
    //                 ],
    //             ],
    //         ],
    //     ];
    // }

    /**
     * Parent navigation items.
     *
     * FUTURE: Enable when Parent Portal features are built.
     */
    // private function getParentNavigation(User $user): array
    // {
    //     return [
    //         [
    //             'title' => 'Family',
    //             'href' => '#',
    //             'icon' => 'Heart',
    //             'permission' => null,
    //             'priority' => 2,
    //             'children' => [
    //                 [
    //                     'title' => 'My Children',
    //                     'href' => '#',
    //                     'icon' => 'Users',
    //                     'permission' => null,
    //                 ],
    //                 [
    //                     'title' => 'Progress',
    //                     'href' => '#',
    //                     'icon' => 'BarChart3',
    //                     'permission' => null,
    //                 ],
    //                 [
    //                     'title' => 'Communications',
    //                     'href' => '#',
    //                     'icon' => 'MessageSquare',
    //                     'permission' => null,
    //                 ],
    //             ],
    //         ],
    //     ];
    // }

    /**
     * Check if user has permission for navigation item.
     *
     * SIMPLIFIED - Permission checks temporarily disabled.
     * All authenticated users can see navigation items.
     *
     * @todo Re-enable when granular permissions are needed
     */
    public function userCanAccessNavigation(User $user, array $navigationItem): bool
    {
        // SIMPLIFIED: Allow all authenticated users access
        // If no permission required, allow access
        // if (! $navigationItem['permission']) {
        //     return true;
        // }

        // PERMISSION-DISABLED: Check permission
        // return $user->can($navigationItem['permission']);

        return true; // Simplified: all authenticated users can access
    }

    /**
     * Filter navigation items user can access.
     *
     * SIMPLIFIED - Permission filtering temporarily disabled.
     *
     * @todo Re-enable when granular permissions are needed
     */
    public function filterAccessibleNavigation(User $user, array $navigation): array
    {
        // SIMPLIFIED: Return all navigation items without permission filtering
        return $navigation;

        // PERMISSION-DISABLED: Original filtering logic
        // return array_filter($navigation, function ($item) use ($user) {
        //     // Check main item permission
        //     if (! $this->userCanAccessNavigation($user, $item)) {
        //         return false;
        //     }
        //
        //     // Filter children
        //     if (isset($item['children'])) {
        //         $item['children'] = array_filter($item['children'], function ($child) use ($user) {
        //             return $this->userCanAccessNavigation($user, $child);
        //         });
        //     }
        //
        //     return true;
        // });
    }
}
