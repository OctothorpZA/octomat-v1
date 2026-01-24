<?php

use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('navigation includes dashboard for all users', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $service = new NavigationService;

    $navigation = $service->getNavigationForUser($user);

    expect($navigation)->toHaveCount(1);
    expect($navigation[0])->toHaveKey('title', 'Dashboard');
    expect($navigation[0])->toHaveKey('href', '/dashboard');
    expect($navigation[0])->toHaveKey('priority', 1);
});

test('super admin gets full admin navigation', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    // Should have Dashboard + Admin Panel
    expect($navigation)->toHaveCount(2);

    // Dashboard should be first (priority 1)
    expect($navigation[0]['title'])->toBe('Dashboard');
    expect($navigation[0]['priority'])->toBe(1);

    // Admin Panel should be second (priority 2)
    expect($navigation[1]['title'])->toBe('Admin Panel');
    expect($navigation[1]['priority'])->toBe(2);
    expect($navigation[1])->toHaveKey('children');

    // Admin Panel should have expected children
    $adminChildren = $navigation[1]['children'];
    expect($adminChildren)->toHaveCount(4);
    expect(collect($adminChildren)->pluck('title')->toArray())->toContain('Dashboard', 'User Management', 'Audit Logs', 'System Settings');
});

test('academy owner gets academy navigation', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Academy Owner');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    expect($navigation)->toHaveCount(2);
    expect($navigation[1]['title'])->toBe('Academy');
    expect($navigation[1])->toHaveKey('children');

    $academyChildren = $navigation[1]['children'];
    expect($academyChildren)->toHaveCount(4);
    expect(collect($academyChildren)->pluck('title')->toArray())->toContain('Dashboard', 'Programs', 'Coaches', 'Athletes');
});

test('club manager gets club navigation', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Club Manager');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    expect($navigation)->toHaveCount(2);
    expect($navigation[1]['title'])->toBe('Club');
    expect($navigation[1])->toHaveKey('children');

    $clubChildren = $navigation[1]['children'];
    expect($clubChildren)->toHaveCount(4);
    expect(collect($clubChildren)->pluck('title')->toArray())->toContain('Dashboard', 'Events', 'Members', 'Facilities');
});

test('coach gets training navigation', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Coach');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    expect($navigation)->toHaveCount(2);
    expect($navigation[1]['title'])->toBe('Training');
    expect($navigation[1])->toHaveKey('children');

    $trainingChildren = $navigation[1]['children'];
    expect($trainingChildren)->toHaveCount(3);
    expect(collect($trainingChildren)->pluck('title')->toArray())->toContain('My Athletes', 'Programs', 'Sessions');
});

test('athlete gets sports navigation', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Athlete');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    expect($navigation)->toHaveCount(2);
    expect($navigation[1]['title'])->toBe('My Sports');
    expect($navigation[1])->toHaveKey('children');

    $sportsChildren = $navigation[1]['children'];
    expect($sportsChildren)->toHaveCount(3);
    expect(collect($sportsChildren)->pluck('title')->toArray())->toContain('Results', 'Training', 'Events');
});

test('parent gets family navigation', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Parent/Guardian');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    expect($navigation)->toHaveCount(2);
    expect($navigation[1]['title'])->toBe('Family');
    expect($navigation[1])->toHaveKey('children');

    $familyChildren = $navigation[1]['children'];
    expect($familyChildren)->toHaveCount(3);
    expect(collect($familyChildren)->pluck('title')->toArray())->toContain('My Children', 'Progress', 'Communications');
});

test('multi-role user gets combined navigation without duplicates', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(['Coach', 'Athlete']); // Both roles

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    // Should have Dashboard + Training + My Sports (3 total, no duplicates)
    expect($navigation)->toHaveCount(3);

    $titles = collect($navigation)->pluck('title')->toArray();
    expect($titles)->toContain('Dashboard', 'Training', 'My Sports');
});

test('navigation is sorted by priority', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    // Verify priorities are in ascending order
    $priorities = collect($navigation)->pluck('priority')->toArray();
    expect($priorities)->toBe([1, 2]); // Dashboard (1), Admin Panel (2)
});

test('unknown role returns default navigation', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    // User with no roles assigned - should get default navigation

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    // Should only have default Dashboard
    expect($navigation)->toHaveCount(1);
    expect($navigation[0]['title'])->toBe('Dashboard');
});

test('user can access navigation permission check works', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $service = new NavigationService;

    // Test item without permission requirement
    $dashboardItem = [
        'title' => 'Dashboard',
        'href' => '/dashboard',
        'permission' => null,
    ];

    expect($service->userCanAccessNavigation($user, $dashboardItem))->toBeTrue();

    // Test item with permission requirement (user won't have)
    $adminItem = [
        'title' => 'Admin',
        'href' => '/admin',
        'permission' => 'viewAdminDashboard',
    ];

    expect($service->userCanAccessNavigation($user, $adminItem))->toBeFalse();
});

test('filter accessible navigation removes unauthorized items', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Admin'); // Give admin permissions

    $service = new NavigationService;

    $mixedNavigation = [
        [
            'title' => 'Dashboard',
            'href' => '/dashboard',
            'permission' => null,
        ],
        [
            'title' => 'Admin',
            'href' => '/admin',
            'permission' => 'system.admin',
        ],
        [
            'title' => 'Restricted',
            'href' => '/restricted',
            'permission' => 'superSecretPermission',
        ],
    ];

    $filtered = $service->filterAccessibleNavigation($user, $mixedNavigation);

    expect($filtered)->toHaveCount(2);
    $titles = collect($filtered)->pluck('title')->toArray();
    expect($titles)->toContain('Dashboard', 'Admin');
    expect($titles)->not()->toContain('Restricted');
});

test('navigation structure includes required fields', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    foreach ($navigation as $item) {
        expect($item)->toHaveKey('title');
        expect($item)->toHaveKey('href');
        expect($item)->toHaveKey('icon');
        expect($item)->toHaveKey('priority');
        // permission can be null, so we don't require it
    }
});

test('admin navigation includes all expected child items', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    $adminPanel = collect($navigation)->firstWhere('title', 'Admin Panel');
    expect($adminPanel)->not()->toBeNull();

    $children = $adminPanel['children'];
    expect($children)->toHaveCount(4);

    // Verify each child has required structure
    foreach ($children as $child) {
        expect($child)->toHaveKey('title');
        expect($child)->toHaveKey('href');
        expect($child)->toHaveKey('icon');
        expect($child)->toHaveKey('permission');
    }
});

test('navigation handles users with multiple admin roles', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(['Super Admin', 'Federation Admin']); // Both admin roles

    $service = new NavigationService;
    $navigation = $service->getNavigationForUser($user);

    // Should still only have one Admin Panel (unique href)
    $adminPanels = collect($navigation)->where('title', 'Admin Panel');
    expect($adminPanels)->toHaveCount(1);
});
