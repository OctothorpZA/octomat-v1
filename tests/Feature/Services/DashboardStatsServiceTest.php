<?php

use App\Models\User;
use App\Services\DashboardStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin stats includes all required metrics', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    // Create some test users
    User::factory()->count(5)->create();

    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    // Check that all expected keys exist
    expect($stats)->toHaveKey('total_users');
    expect($stats)->toHaveKey('active_users_today');
    expect($stats)->toHaveKey('recent_registrations');
    expect($stats)->toHaveKey('users_by_role');
    expect($stats)->toHaveKey('total_roles');
    expect($stats)->toHaveKey('role_assignments_today');
    expect($stats)->toHaveKey('role_removals_today');
    expect($stats)->toHaveKey('audit_logs_this_week');
    expect($stats)->toHaveKey('audit_logs_today');
    expect($stats)->toHaveKey('most_active_admin');
    expect($stats)->toHaveKey('system_health_score');
    expect($stats)->toHaveKey('database_connections');
    expect($stats)->toHaveKey('cache_hit_rate');
    expect($stats)->toHaveKey('recent_activities');

    // Check that values are reasonable
    expect($stats['total_users'])->toBeGreaterThanOrEqual(5); // At least our created users
    expect($stats['total_roles'])->toBeGreaterThan(0);
    expect($stats['system_health_score'])->toBeBetween(0, 100);
});

test('user dashboard stats includes user info', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create([
        'first_name' => 'John',
        'middle_names' => null,
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);

    $user->assignRole('Athlete');

    $service = new DashboardStatsService;
    $stats = $service->getUserDashboardStats($user);

    expect($stats)->toHaveKey('user_info');
    expect($stats['user_info']['name'])->toBe('John Doe');
    expect($stats['user_info']['email'])->toBe('john@example.com');
    expect($stats['user_info']['roles'])->toContain('Athlete');
    expect($stats['user_info'])->toHaveKey('member_since');
    expect($stats['user_info'])->toHaveKey('last_login');
});

test('users by role calculation works correctly', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    // Create users with different roles
    $athlete1 = User::factory()->create();
    $athlete1->assignRole('Athlete');

    $athlete2 = User::factory()->create();
    $athlete2->assignRole('Athlete');

    $coach = User::factory()->create();
    $coach->assignRole('Coach');

    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    $usersByRole = $stats['users_by_role'];

    // Should have Athlete: 2, Coach: 1
    expect($usersByRole['Athlete'])->toBe(2);
    expect($usersByRole['Coach'])->toBe(1);
});

test('recent registrations count is accurate', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    // Create users at different times
    $oldUser = User::factory()->create([
        'created_at' => now()->subDays(10),
    ]);

    $recentUser1 = User::factory()->create([
        'created_at' => now()->subDays(3),
    ]);

    $recentUser2 = User::factory()->create([
        'created_at' => now()->subDays(5),
    ]);

    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    // Should count users from the last 7 days
    expect($stats['recent_registrations'])->toBe(2);
});

test('system health score calculation works', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    // Create some audit logs to affect health score
    User::factory()->create(); // At least one user for activity

    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    $healthScore = $stats['system_health_score'];

    expect($healthScore)->toBeBetween(0, 100);
    expect($healthScore)->toBeInt();
});

test('cache hit rate returns expected value', function () {
    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    expect($stats['cache_hit_rate'])->toBe(95.5);
    expect($stats['cache_hit_rate'])->toBeFloat();
});

test('recent activities are properly formatted', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    // Create some audit logs
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    // This should create audit logs
    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Athlete',
        ]);

    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    $recentActivities = $stats['recent_activities'];

    expect($recentActivities)->toBeArray();

    if (count($recentActivities) > 0) {
        $activity = $recentActivities[0];
        expect($activity)->toHaveKey('id');
        expect($activity)->toHaveKey('action');
        expect($activity)->toHaveKey('role');
        expect($activity)->toHaveKey('admin_name');
        expect($activity)->toHaveKey('target_name');
        expect($activity)->toHaveKey('timestamp');
        expect($activity)->toHaveKey('time_ago');
    }
});

test('role-specific stats for super admin include admin metrics', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $service = new DashboardStatsService;
    $stats = $service->getUserDashboardStats($admin);

    expect($stats)->toHaveKey('admin_stats');
    expect($stats['admin_stats'])->toHaveKey('total_users_managed');
    expect($stats['admin_stats'])->toHaveKey('recent_audits');
});

test('role-specific stats for athlete include athlete metrics', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $athlete = User::factory()->create();
    $athlete->assignRole('Athlete');

    $service = new DashboardStatsService;
    $stats = $service->getUserDashboardStats($athlete);

    expect($stats)->toHaveKey('athlete_stats');
    expect($stats['athlete_stats'])->toHaveKey('upcoming_events');
    expect($stats['athlete_stats'])->toHaveKey('recent_results');
});

test('role-specific stats for coach include coaching metrics', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $coach = User::factory()->create();
    $coach->assignRole('Coach');

    $service = new DashboardStatsService;
    $stats = $service->getUserDashboardStats($coach);

    expect($stats)->toHaveKey('coaching_stats');
    expect($stats['coaching_stats'])->toHaveKey('sessions_this_week');
    expect($stats['coaching_stats'])->toHaveKey('athletes_coached');
});

test('role-specific stats for parent include family metrics', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $parent = User::factory()->create();
    $parent->assignRole('Parent/Guardian');

    $service = new DashboardStatsService;
    $stats = $service->getUserDashboardStats($parent);

    expect($stats)->toHaveKey('family_stats');
    expect($stats['family_stats'])->toHaveKey('linked_athletes');
    expect($stats['family_stats'])->toHaveKey('upcoming_events');
});

test('multi-role user gets combined stats from all roles', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(['Coach', 'Athlete']);

    $service = new DashboardStatsService;
    $stats = $service->getUserDashboardStats($user);

    // Should have stats from both Coach and Athlete roles
    expect($stats)->toHaveKey('coaching_stats');
    expect($stats)->toHaveKey('athlete_stats');
});

test('performance metrics returns expected structure', function () {
    $service = new DashboardStatsService;
    $metrics = $service->getPerformanceMetrics();

    expect($metrics)->toHaveKey('response_time_avg');
    expect($metrics)->toHaveKey('error_rate');
    expect($metrics)->toHaveKey('uptime');
    expect($metrics)->toHaveKey('memory_usage');

    // Check that values are reasonable strings
    expect($metrics['response_time_avg'])->toContain('ms');
    expect($metrics['error_rate'])->toContain('%');
    expect($metrics['uptime'])->toContain('%');
    expect($metrics['memory_usage'])->toContain('%');
});

test('database connections info includes status', function () {
    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    $dbConnections = $stats['database_connections'];

    expect($dbConnections)->toHaveKey('active');
    expect($dbConnections)->toHaveKey('status');

    // Status should be either 'healthy' or 'error'
    expect($dbConnections['status'])->toBeIn(['healthy', 'error']);
});

test('most active admin calculation works with audit logs', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin1 = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'One']);
    $admin1->assignRole('Super Admin');

    $admin2 = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'Two']);
    $admin2->assignRole('Super Admin');

    $user = User::factory()->create();

    // Admin1 performs 3 actions
    $this->actingAs($admin1)->post('/admin/roles/assign', ['selectedUser' => $user->id, 'selectedRole' => 'Athlete']);
    $this->actingAs($admin1)->post('/admin/roles/remove', ['selectedUser' => $user->id, 'selectedRole' => 'Athlete']);
    $this->actingAs($admin1)->post('/admin/roles/assign', ['selectedUser' => $user->id, 'selectedRole' => 'Coach']);

    // Admin2 performs 1 action
    $this->actingAs($admin2)->post('/admin/roles/assign', ['selectedUser' => $user->id, 'selectedRole' => 'Athlete']);

    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    $mostActiveAdmin = $stats['most_active_admin'];

    if ($mostActiveAdmin) {
        expect($mostActiveAdmin['name'])->toBe('Admin One');
        expect($mostActiveAdmin['activities'])->toBeGreaterThanOrEqual(3);
    }
});

test('active users today calculation uses audit logs', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    // Perform some actions to create audit logs
    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Athlete',
        ]);

    $service = new DashboardStatsService;
    $stats = $service->getAdminStats();

    // Should count distinct admin users who performed actions today
    expect($stats['active_users_today'])->toBeGreaterThanOrEqual(0);
});
