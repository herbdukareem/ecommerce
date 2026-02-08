<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Delete existing roles and permissions with wrong guard
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create permissions
        $permissions = [
            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            
            // Category permissions
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Vendor permissions
            'view vendors',
            'create vendors',
            'edit vendors',
            'delete vendors',
            'approve vendors',
            
            // Order permissions
            'view orders',
            'edit orders',
            'delete orders',
            'fulfill orders',
            
            // Shipping permissions
            'view shipping',
            'edit shipping',
            
            // Settings permissions
            'view settings',
            'edit settings',
            
            // User permissions
            'view users',
            'edit users',
            'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'sanctum']);
        $vendorRole = Role::firstOrCreate(['name' => 'Vendor', 'guard_name' => 'sanctum']);
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'sanctum']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Vendor gets limited permissions
        $vendorRole->givePermissionTo([
            'view products',
            'create products',
            'edit products',
            'view orders',
            'fulfill orders',
        ]);

        // Customer gets minimal permissions
        $customerRole->givePermissionTo([
            'view products',
            'view categories',
        ]);
    }
}
