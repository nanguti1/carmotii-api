<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'user',
            'host',
            'admin',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create permissions
        $permissions = [
            'manage cars',
            'manage bookings',
            'manage users',
            'manage reviews',
            'manage payments',
            'approve cars',
            'verify users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());

        $hostRole = Role::findByName('host');
        $hostRole->givePermissionTo([
            'manage cars',
            'manage bookings',
        ]);
    }
}
