<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'first_name' => 'Test',
            // 'middle_names' => null,
            'last_name' => 'User',
            'date_of_birth' => '1990-01-01',
            'email' => 'test@example.com',
        ]);

        $this->call([
            RoleSeeder::class,
        ]);
    }
}
