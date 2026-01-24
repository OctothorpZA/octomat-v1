<?php

use App\Models\User;

test('role assignment creates audit log entry', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();

    $this->actingAs($admin)->post('/admin/roles/assign', [
        'selectedUser' => $user->id,
        'selectedRole' => 'Coach',
    ]);

    expect(\App\Models\AuditLog::count())->toBe(1);

    $auditLog = \App\Models\AuditLog::first();
    expect($auditLog->admin_id)->toBe($admin->id);
    expect($auditLog->target_user_id)->toBe($user->id);
    expect($auditLog->action)->toBe('assigned');
    expect($auditLog->role)->toBe('Coach');
    expect($auditLog->ip_address)->toBe('127.0.0.1');
});

test('role removal creates audit log entry', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user = User::factory()->create();
    $user->assignRole('Coach');

    $this->actingAs($admin)->post('/admin/roles/remove', [
        'selectedUser' => $user->id,
        'selectedRole' => 'Coach',
    ]);

    expect(\App\Models\AuditLog::count())->toBe(1);

    $auditLog = \App\Models\AuditLog::first();
    expect($auditLog->action)->toBe('removed');
    expect($auditLog->role)->toBe('Coach');
});

test('audit log includes proper context', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'User']);
    $admin->assignRole('Super Admin');

    $user = User::factory()->create(['first_name' => 'Target', 'last_name' => 'User']);

    $this->actingAs($admin)->post('/admin/roles/assign', [
        'selectedUser' => $user->id,
        'selectedRole' => 'Athlete',
    ]);

    $auditLog = \App\Models\AuditLog::first();
    expect($auditLog->admin_name)->toBe('Admin User');
    expect($auditLog->target_user_name)->toBe('Target User');
    expect($auditLog->role)->toBe('Athlete');
});

test('audit service provides statistics', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create some audit logs
    $this->actingAs($admin)->post('/admin/roles/assign', [
        'selectedUser' => $user1->id,
        'selectedRole' => 'Coach',
    ]);

    $this->actingAs($admin)->post('/admin/roles/assign', [
        'selectedUser' => $user2->id,
        'selectedRole' => 'Athlete',
    ]);

    $stats = app(\App\Services\AuditService::class)->getAuditStats();

    expect($stats['total_logs'])->toBe(2);
    expect($stats['role_assignments'])->toBe(2);
    expect($stats['role_removals'])->toBe(0);
});

test('audit log viewer is accessible to super admins', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Super Admin');

    $response = $this->actingAs($admin)->get('/admin/audit');

    $response->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('admin/audit-log')
            ->has('auditLogs')
            ->has('stats')
        );
});

test('audit log viewer is not accessible to regular users', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('General User');

    $this->actingAs($user)->get('/admin/audit')->assertForbidden();
});
