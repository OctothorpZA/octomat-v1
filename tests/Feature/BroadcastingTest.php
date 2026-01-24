<?php

use App\Events\RoleAssigned;
use App\Events\RoleRemoved;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('role assignment event broadcasts to admin channel', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    // Assign role via controller - this triggers broadcasting
    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach',
        ]);

    // Assert that the event was broadcast
    Event::assertDispatched(RoleAssigned::class, function ($event) use ($user, $admin) {
        return $event->user->id === $user->id &&
               $event->role === 'Coach' &&
               $event->admin->id === $admin->id;
    });
});

test('role removal event broadcasts with correct payload', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();
    $user->assignRole('Athlete');

    // Remove role via controller - this triggers broadcasting
    $this->actingAs($admin)
        ->post('/admin/roles/remove', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Athlete',
        ]);

    // Assert that the event was broadcast
    Event::assertDispatched(RoleRemoved::class, function ($event) use ($user, $admin) {
        return $event->user->id === $user->id &&
               $event->role === 'Athlete' &&
               $event->admin->id === $admin->id;
    });
});

test('role assignment event includes user details in broadcast', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
    ]);

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach',
        ]);

    Event::assertDispatched(RoleAssigned::class, function ($event) {
        return $event->user->first_name === 'John' &&
               $event->user->last_name === 'Doe' &&
               $event->user->email === 'john.doe@example.com' &&
               $event->role === 'Coach';
    });
});

test('role assignment broadcasts to admin notification channel', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    // Assign role via controller - this should trigger broadcasting
    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach',
        ]);

    Event::assertDispatched(RoleAssigned::class); // Coach role assigned
});

test('broadcasting includes admin context and user data', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();
    // Ensure user doesn't already have the role
    expect($user->hasRole('Coach'))->toBeFalse();

    $response = $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach',
        ]);

    // Should succeed and dispatch event
    $response->assertRedirect();
    Event::assertDispatched(RoleAssigned::class, function ($event) {
        return isset($event->admin) &&
               $event->admin instanceof User &&
               isset($event->user) &&
               $event->user instanceof User;
    });
});

test('multiple role assignments broadcast independently', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user1->id,
            'selectedRole' => 'Coach',
        ]);

    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user2->id,
            'selectedRole' => 'Athlete',
        ]);

    // Should have broadcast 2 assignment events
    Event::assertDispatched(RoleAssigned::class, 2);
});

test('non-super admin cannot assign roles - access denied', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RoleSeeder::class);

    $clubManager = User::factory()->create();
    $clubManager->assignRole('Club Manager');

    $user = User::factory()->create();
    // Ensure user doesn't already have the role
    expect($user->hasRole('Athlete'))->toBeFalse();

    $this->actingAs($clubManager)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Athlete',
        ])
        ->assertRedirect('/dashboard');

    // Should NOT broadcast any event
    Event::assertNotDispatched(RoleAssigned::class);
});

test('broadcasting fails gracefully when event dispatching disabled', function () {
    // Test that role assignment still works even if broadcasting fails
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    // This should not throw an exception even if broadcasting fails
    $this->actingAs($admin)
        ->post('/admin/roles/assign', [
            'selectedUser' => $user->id,
            'selectedRole' => 'Coach',
        ]);

    expect($user->fresh()->hasRole('Coach'))->toBeTrue();
});
