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
        $this->call([
            RoleSeeder::class,
            PricingPlanSeeder::class,
        ]);

        // Create admin user
        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@carmotii.com',
        ]);
        $admin->assignRole('admin');

        // Create test host user
        $host = User::factory()->create([
            'first_name' => 'Host',
            'last_name' => 'User',
            'email' => 'host@carmotii.com',
        ]);
        $host->assignRole('host');

        // Create regular test user
        User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@carmotii.com',
        ]);
    }
}
