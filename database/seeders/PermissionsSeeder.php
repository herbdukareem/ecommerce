<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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

        // Create permissions
        $permissions = [
            'dashboard.view',

            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            
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
            'order.view',
            'order.update',
            'order.receipt.download',
            'admin-order.create',
            
            // Shipping permissions
            'view shipping',
            'edit shipping',
            'delivery-partner.view',
            'delivery-partner.create',
            'delivery-partner.update',
            'dispatch-rider.view',
            'dispatch-rider.create',
            'dispatch-rider.update',
            'dispatch-assignment.manage',
            'dispatch-assignment.update-own',
            
            // Settings permissions
            'view settings',
            'edit settings',
            'settings.view',
            'settings.update',
            
            // User permissions
            'view users',
            'edit users',
            'delete users',
            'user.view',
            'user.update',
            'role.view',
            'role.manage',
            'permission.view',
            'permission.manage',

            // New operations permissions
            'dispatch-time.view',
            'dispatch-time.create',
            'dispatch-time.update',
            'dispatch-time.delete',
            'city.view',
            'city.create',
            'city.update',
            'city.delete',
            'area.view',
            'area.create',
            'area.update',
            'area.delete',
            'product-image.manage',
            'product-option.view',
            'product-option.create',
            'product-option.update',
            'product-option.delete',
            'inventory.view',
            'inventory.add-stock',
            'inventory.ledger.view',
            'inventory.expiry.manage',
            'profit-report.view',
            'analytics.view',
            'payment.view',
            'payment.update',
            'report.view',
            'newsletter.view',
            'newsletter.create',
            'newsletter.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create roles and assign permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'sanctum']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'sanctum']);
        $vendorRole = Role::firstOrCreate(['name' => 'Vendor', 'guard_name' => 'sanctum']);
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'sanctum']);
        $inventoryRole = Role::firstOrCreate(['name' => 'Inventory Manager', 'guard_name' => 'sanctum']);
        $salesRole = Role::firstOrCreate(['name' => 'Sales/Admin Order Officer', 'guard_name' => 'sanctum']);
        $dispatchManagerRole = Role::firstOrCreate(['name' => 'Dispatch Manager', 'guard_name' => 'sanctum']);
        $dispatchRiderRole = Role::firstOrCreate(['name' => 'Dispatch Rider', 'guard_name' => 'sanctum']);
        $financeRole = Role::firstOrCreate(['name' => 'Accountant/Finance', 'guard_name' => 'sanctum']);

        // Super Admin and existing Admin users retain full access after seeding.
        $superAdminRole->syncPermissions(Permission::all());
        $adminRole->syncPermissions(Permission::all());

        $inventoryRole->syncPermissions([
            'dashboard.view',
            'inventory.view',
            'inventory.add-stock',
            'inventory.ledger.view',
            'inventory.expiry.manage',
            'product.view',
            'view products',
            'product-option.view',
            'profit-report.view',
        ]);

        $salesRole->syncPermissions([
            'dashboard.view',
            'order.view',
            'order.update',
            'order.receipt.download',
            'admin-order.create',
            'view orders',
            'edit orders',
            'view products',
        ]);

        $dispatchManagerRole->syncPermissions([
            'dashboard.view',
            'order.view',
            'order.update',
            'delivery-partner.view',
            'delivery-partner.create',
            'delivery-partner.update',
            'dispatch-rider.view',
            'dispatch-rider.create',
            'dispatch-rider.update',
            'dispatch-assignment.manage',
            'dispatch-time.view',
            'dispatch-time.create',
            'dispatch-time.update',
            'dispatch-time.delete',
        ]);

        $dispatchRiderRole->syncPermissions([
            'dispatch-assignment.update-own',
        ]);

        $financeRole->syncPermissions([
            'dashboard.view',
            'order.view',
            'payment.view',
            'payment.update',
            'profit-report.view',
            'analytics.view',
            'report.view',
            'order.receipt.download',
        ]);

        // Vendor gets limited permissions
        $vendorRole->syncPermissions([
            'view products',
            'create products',
            'edit products',
            'view orders',
            'fulfill orders',
        ]);

        // Customer gets minimal permissions
        $customerRole->syncPermissions([
            'view products',
            'view categories',
        ]);
    }
}
