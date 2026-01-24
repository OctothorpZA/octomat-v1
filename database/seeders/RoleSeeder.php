<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create basic permissions
        $permissions = [
            'system.admin', 'system.audit',
            'users.manage', 'roles.manage',
            'federation.admin', 'federation.members.manage',
            'academies.create', 'academies.view', 'academies.manage',
            'clubs.manage', 'club.members.manage',
            'events.create', 'events.view', 'events.manage',
            'profile.basic.manage',
            'assign-roles', // Permission for role assignment
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            [
                'name' => 'Super Admin',
                'display_name' => 'System Administrator',
                'level' => 1000,
                'module' => 'system',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Federation Admin',
                'display_name' => 'Federation Administrator',
                'level' => 900,
                'module' => 'federation',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Event Organiser',
                'display_name' => 'Event Organiser',
                'level' => 800,
                'module' => 'events',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Affiliate Manager',
                'display_name' => 'Affiliate Manager',
                'level' => 750,
                'module' => 'federation',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Academy Owner',
                'display_name' => 'Academy Owner',
                'level' => 700,
                'module' => 'academy',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Club Manager',
                'display_name' => 'Club Manager',
                'level' => 600,
                'module' => 'club',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Club Admin',
                'display_name' => 'Club Administrator',
                'level' => 500,
                'module' => 'club',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Coach',
                'display_name' => 'Coach',
                'level' => 400,
                'module' => 'athletics',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Event Staff',
                'display_name' => 'Event Staff',
                'level' => 300,
                'module' => 'events',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Parent/Guardian',
                'display_name' => 'Parent/Guardian',
                'level' => 250,
                'module' => 'family',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Athlete',
                'display_name' => 'Athlete',
                'level' => 200,
                'module' => 'athletics',
                'guard_name' => 'web',
            ],
            [
                'name' => 'General User',
                'display_name' => 'General User',
                'level' => 100,
                'module' => 'core',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(['name' => $roleData['name']], $roleData);

            // Assign basic permissions based on role
            $rolePermissions = match ($roleData['name']) {
                'Super Admin' => ['system.admin', 'users.manage', 'roles.manage', 'assign-roles'],
                'Federation Admin' => ['system.audit', 'users.manage', 'federation.admin'],
                'Event Organiser' => ['events.create', 'events.view', 'events.manage'],
                'Affiliate Manager' => ['academies.view', 'federation.members.manage'],
                'Academy Owner' => ['academies.create', 'academies.view', 'academies.manage'],
                'Club Manager' => ['academies.view', 'clubs.manage', 'club.members.manage'],
                'Club Admin' => ['clubs.manage', 'club.members.manage'],
                'Coach' => ['events.view'],
                'Event Staff' => ['events.view'],
                'General User' => ['profile.basic.manage'],
                default => []
            };

            if (! empty($rolePermissions)) {
                $role->givePermissionTo($rolePermissions);
            }
        }
    }
}
