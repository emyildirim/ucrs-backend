<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Example: Create test users for each role
        
        // Admin user
        // User::factory()->admin()->create([
        //     'full_name' => 'Admin User',
        //     'email' => 'admin@ucrs.test',
        // ]);

        // Instructor user
        // User::factory()->instructor()->create([
        //     'full_name' => 'Test Instructor',
        //     'email' => 'instructor@ucrs.test',
        // ]);

        // Student user
        // User::factory()->create([
        //     'full_name' => 'Test Student',
        //     'email' => 'student@ucrs.test',
        // ]);
    }
}
