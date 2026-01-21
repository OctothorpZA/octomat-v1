<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Demo users for each role
        $demoUsers = [
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'super@demo.com',
                'role' => 'Super Admin',
            ],
            [
                'first_name' => 'Coach',
                'last_name' => 'Johnson',
                'email' => 'coach@demo.com',
                'role' => 'Coach',
            ],
            [
                'first_name' => 'Alex',
                'last_name' => 'Athlete',
                'email' => 'athlete@demo.com',
                'role' => 'Athlete',
            ],
            [
                'first_name' => 'Parent',
                'last_name' => 'Smith',
                'email' => 'parent@demo.com',
                'role' => 'Parent/Guardian',
            ],
            [
                'first_name' => 'Academy',
                'last_name' => 'Owner',
                'email' => 'academy@demo.com',
                'role' => 'Academy Owner',
            ],
            [
                'first_name' => 'Club',
                'last_name' => 'Manager',
                'email' => 'club@demo.com',
                'role' => 'Club Manager',
            ],
            [
                'first_name' => 'General',
                'last_name' => 'User',
                'email' => 'user@demo.com',
                'role' => 'General User',
            ],
        ];

        foreach ($demoUsers as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $userData['password'] = Hash::make('password');
            $userData['email_verified_at'] = now();
            $userData['date_of_birth'] = now()->subYears(rand(18, 50))->format('Y-m-d');

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            $user->assignRole($role);
        }
    }
}
