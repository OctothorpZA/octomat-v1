<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('super admin sees admin widgets on unified dashboard', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('dashboard')
            ->has('user')
            ->has('userRoles')
            ->has('widgets')
            ->where('userRoles', ['General User', 'Super Admin']) // All roles
        );
});

test('super admin dashboard includes admin-specific widgets', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertInertia(fn ($inertia) => $inertia
        ->has('widgets', 5) // Super Admin (3) + General User (2) = 5 widgets
        ->where('widgets.0.title', 'System Overview') // Highest priority admin widget
    );
});

test('athlete sees athlete-specific widgets on unified dashboard', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $athlete = User::factory()->create();
    $athlete->assignRole('Athlete');

    $response = $this->actingAs($athlete)->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('dashboard')
            ->has('widgets', 5) // Athlete (3) + General User (2) = 5 widgets
            ->where('widgets.0.title', 'My Profile') // Highest priority athlete widget
        );
});

test('multi-role user sees aggregated widgets from all roles', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(['Athlete', 'General User']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->has('userRoles', 2) // Both roles
            ->has('widgets', 5) // Athlete (3) + General User (2) = 5 widgets
        );
});

test('unauthorized user cannot access admin role assignment page', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Athlete'); // No admin permissions

    $response = $this->actingAs($user)->get('/admin/roles/assign');

    $response->assertForbidden();
});

test('coach cannot see super admin widgets', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $coach = User::factory()->create();
    $coach->assignRole('Coach');

    $response = $this->actingAs($coach)->get('/dashboard');

    $response->assertInertia(fn ($inertia) => $inertia
        ->has('widgets')
        ->where('widgets', function ($widgets) {
            // Ensure no admin widgets are present
            foreach ($widgets as $widget) {
                if (in_array($widget['title'], ['System Overview', 'User Management', 'Security Audit'])) {
                    return false;
                }
            }

            return true;
        })
    );
});

test('super admin can access role assignment page with user list and pagination', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    // Create some test users
    User::factory()->count(15)->create();

    $response = $this->actingAs($admin)->get('/admin/roles/assign');

    $response->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('admin/role-assignment')
            ->has('users.data', 15) // Paginated to 15 per page
            ->has('users.current_page')
            ->has('users.last_page')
            ->where('users.total', 16) // 15 created + 1 admin = 16 total
            ->has('roles')
        );
});
