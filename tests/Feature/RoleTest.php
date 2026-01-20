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

test('role hierarchy works', function () {
    $user = User::factory()->create();
    $user->assignRole('General User');
    $user->assignRole('Coach');

    expect($user->getHighestRoleLevel())->toBe(400); // Coach level is 400
    expect($user->getPrimaryRole()->name)->toBe('Coach');
});

test('role level hierarchy works', function () {
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

test('non-super admin cannot assign super admin role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Academy Owner'); // Not Super Admin

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Super Admin',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['authorization']);
});

test('cannot assign high-level roles to yourself', function () {
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

test('cannot assign roles above your authority level', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Club Manager'); // Level 600

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Federation Admin', // Level 900, above Club Manager
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['authorization']);
});

test('prevents conflicting role assignments at similar levels', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();
    $user->assignRole('Athlete'); // Level 200

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Parent/Guardian', // Level 250, within 100 of Athlete (200)
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['conflict']);
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

test('club manager can assign roles within their authority', function () {
    $clubManager = User::factory()->create();
    $clubManager->assignRole('Club Manager'); // Level 600

    $user = User::factory()->create();

    $this->actingAs($clubManager)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach', // Level 400, below Club Manager
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->hasRole('Coach'))->toBeTrue();
});

// Integration Tests: User Journey Scenarios

test('complete user registration and role assignment journey', function () {
    // Simulate user registration
    $user = User::factory()->create();

    // Verify default role assignment
    expect($user->hasRole('General User'))->toBeTrue();
    expect($user->getHighestRoleLevel())->toBe(100);

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

    // Verify role change
    $user->refresh();
    expect($user->hasRole('Athlete'))->toBeTrue();
    expect($user->hasRole('General User'))->toBeFalse(); // Should be replaced
    expect($user->getHighestRoleLevel())->toBe(200);
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
