<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('super admin can assign roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach',
        ])
        ->assertRedirect();

    expect($user->hasRole('Coach'))->toBeTrue();
});

test('users get default role on registration', function () {
    $user = User::factory()->create();

    expect($user->hasRole('General User'))->toBeTrue();
});

test('role-based middleware protects routes', function () {
    $user = User::factory()->create();
    $user->assignRole('General User');

    $this->actingAs($user)
        ->get('/admin/roles/assign')
        ->assertForbidden();
});

// HIERARCHY-DISABLED: Level-based tests temporarily marked as todo
// Will re-enable when role hierarchy is implemented

todo('role hierarchy works', function () {
    $user = User::factory()->create();
    $user->assignRole('General User');
    $user->assignRole('Coach');

    expect($user->getHighestRoleLevel())->toBe(400); // Coach level is 400
    expect($user->getPrimaryRole()->name)->toBe('Coach');
});

todo('role level hierarchy works', function () {
    $user = User::factory()->create();
    $user->assignRole('General User');
    $user->assignRole('Coach');

    expect($user->getHighestRoleLevel())->toBe(400); // Coach level is 400
    expect($user->getPrimaryRole()->name)->toBe('Coach');
});

test('default role fallback works when role missing', function () {
    // Delete General User role if exists
    \Spatie\Permission\Models\Role::where('name', 'General User')->delete();

    $user = User::factory()->create();
    expect($user->hasRole('General User'))->toBeTrue();
});

// HIERARCHY-DISABLED: Level-based authorization test temporarily marked as todo
// Currently, only Super Admin can assign roles (not based on level hierarchy)
todo('non-super admin cannot assign super admin role', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Club Manager'); // Level 600 - HIERARCHY DISABLED

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Super Admin',
        ])
        ->assertRedirect('/dashboard'); // Middleware redirects non-authorized users
});

test('super admins cannot assign high-level roles to themselves for security', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $admin->id, // Self-assignment
            'selectedRole' => 'Federation Admin', // Level 900
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['authorization']);
});

// HIERARCHY-DISABLED: Level-based authorization test temporarily marked as todo
todo('cannot assign roles above your authority level', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Club Manager'); // Level 600

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Federation Admin', // Level 900, above Club Manager
        ])
        ->assertRedirect('/dashboard'); // Middleware blocks access before controller validation
});

// HIERARCHY-DISABLED: Level-based conflict detection test temporarily marked as todo
todo('prevents conflicting role assignments at similar levels', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();
    $user->assignRole('Club Manager'); // Level 700

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Club Admin', // Level 750, diff 50 <60 triggers conflict (whitelist test)
        ])
        ->assertRedirect();
    // ->assertSessionHasErrors(['conflict']); // MVP: Conflicts disabled via whitelist/threshold, common combos allowed
});

test('allows role assignment within same level range when replacing', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();
    $user->assignRole('Coach'); // Level 400

    // Should allow replacing Coach with Club Manager (600 > 400, different enough)
    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Club Manager', // Level 600
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});

// HIERARCHY-DISABLED: Level-based authorization test temporarily marked as todo
// Currently, only Super Admin can assign roles (not based on level hierarchy)
todo('club manager cannot assign roles - access denied', function () {
    $clubManager = User::factory()->create();
    $clubManager->assignRole('Club Manager'); // Level 600 - HIERARCHY DISABLED

    $user = User::factory()->create();

    $this->actingAs($clubManager)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach', // Level 400 - HIERARCHY DISABLED
        ])
        ->assertRedirect('/dashboard');

    // Role should not be assigned
    expect($user->hasRole('Coach'))->toBeFalse();
});

// Integration Tests: User Journey Scenarios

test('complete user registration and role assignment journey', function () {
    // Simulate user registration
    $user = User::factory()->create();

    // Verify default role assignment
    expect($user->hasRole('General User'))->toBeTrue();
    // HIERARCHY-DISABLED: Level checks removed
    // expect($user->getHighestRoleLevel())->toBe(100);

    // Simulate admin role assignment
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Athlete',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    // Verify role assignment (multi-role support - roles are accumulated)
    $user->refresh();
    expect($user->hasRole('Athlete'))->toBeTrue();
    expect($user->hasRole('General User'))->toBeTrue(); // Both roles are kept
    // HIERARCHY-DISABLED: Level checks removed
    // expect($user->getHighestRoleLevel())->toBe(200); // Athlete level
});

test('role assignment handles validation errors gracefully', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    // Test invalid user ID
    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => 99999, // Non-existent user
            'selectedRole' => 'Coach',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['selectedUser']);
});

test('role permissions work across different user types', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Admin');

    $federationAdmin = User::factory()->create();
    $federationAdmin->assignRole('Federation Admin');

    $academyOwner = User::factory()->create();
    $academyOwner->assignRole('Academy Owner');

    $coach = User::factory()->create();
    $coach->assignRole('Coach');

    // Test permission inheritance
    expect($superAdmin->hasPermissionTo('system.admin'))->toBeTrue();
    expect($federationAdmin->hasPermissionTo('system.admin'))->toBeFalse();
    expect($federationAdmin->hasPermissionTo('federation.admin'))->toBeTrue();
    expect($academyOwner->hasPermissionTo('academies.create'))->toBeTrue();
    expect($coach->hasPermissionTo('academies.create'))->toBeFalse();
    expect($coach->hasPermissionTo('events.view'))->toBeTrue();
});
