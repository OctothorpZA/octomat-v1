<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create granular permissions
        $permissions = [
            'view.athletes',
            'manage.programs',
            'view.events',
            'manage.events',
            'view.clubs',
            'manage.clubs',
            'view.users',
            'manage.users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to Super Admin
        $superAdmin = Role::findByName('Super Admin');
        $superAdmin->givePermissionTo(Permission::all());

        // Coach specific permissions
        $coach = Role::findByName('Coach');
        $coach->givePermissionTo(['view.athletes', 'manage.programs']);

        // Club Manager
        $clubManager = Role::findByName('Club Manager');
        $clubManager->givePermissionTo(['view.clubs', 'manage.clubs', 'club.members.manage']);
    }
}
