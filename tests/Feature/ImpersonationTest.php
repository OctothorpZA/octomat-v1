<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('super admin can impersonate other users', function () {
    // Seed roles for test
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Admin');

    $regularUser = User::factory()->create();
    $regularUser->assignRole('General User');

    $this->actingAs($superAdmin);

    // Test impersonation methods exist
    expect($superAdmin->canImpersonate())->toBeTrue();
    expect($regularUser->canBeImpersonated())->toBeTrue();

    // Test impersonation works using Laravel Impersonate
    $superAdmin->impersonate($regularUser);

    // After impersonation, we should be the regular user
    expect(auth()->user()->isImpersonated())->toBeTrue();
    expect(auth()->id())->toBe($regularUser->id);
});

test('regular users cannot impersonate', function () {
    // Seed roles for test
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $regularUser = User::factory()->create();
    $regularUser->assignRole('General User');

    $otherUser = User::factory()->create();
    $otherUser->assignRole('General User');

    $this->actingAs($regularUser);

    expect($regularUser->canImpersonate())->toBeFalse();
});

test('super admins cannot be impersonated', function () {
    // Seed roles for test
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('Super Admin');

    $regularUser = User::factory()->create();
    $regularUser->assignRole('General User');

    expect($superAdmin->canBeImpersonated())->toBeFalse();
});
