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
            'selectedRole' => 'Coach'
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
            'selectedRole' => 'Super Admin'
        ])
        ->assertForbidden();
});
