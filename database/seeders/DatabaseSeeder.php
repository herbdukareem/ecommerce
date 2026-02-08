<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sku;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRule;
use App\Models\ShippingMethod;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'sanctum']);
        $vendorRole = Role::firstOrCreate(['name' => 'Vendor', 'guard_name' => 'sanctum']);
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'sanctum']);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole($adminRole);

        // Create vendor user
        $vendor = User::firstOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name' => 'Vendor User',
                'password' => Hash::make('password'),
            ]
        );
        $vendor->assignRole($vendorRole);

        // Create customer user
        $customer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Customer User',
                'password' => Hash::make('password'),
            ]
        );
        $customer->assignRole($customerRole);

        // Create categories
        $electronics = Category::firstOrCreate(['name' => 'Electronics', 'parent_id' => null]);
        $clothing = Category::firstOrCreate(['name' => 'Clothing', 'parent_id' => null]);
        $books = Category::firstOrCreate(['name' => 'Books', 'parent_id' => null]);
        
        Category::firstOrCreate(['name' => 'Smartphones', 'parent_id' => $electronics->id]);
        Category::firstOrCreate(['name' => 'Laptops', 'parent_id' => $electronics->id]);
        Category::firstOrCreate(['name' => 'Men', 'parent_id' => $clothing->id]);
        Category::firstOrCreate(['name' => 'Women', 'parent_id' => $clothing->id]);

        // Create warehouse
        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'Main Warehouse'],
            ['vendor_id' => $vendor->id]
        );

        // Create sample products
        $products = [
            [
                'title' => 'iPhone 15 Pro',
                'description' => 'Latest iPhone with A17 Pro chip',
                'base_price' => 999.99,
                'category' => $electronics,
                'stock' => 50,
            ],
            [
                'title' => 'MacBook Pro 16"',
                'description' => 'Powerful laptop for professionals',
                'base_price' => 2499.99,
                'category' => $electronics,
                'stock' => 25,
            ],
            [
                'title' => 'Classic T-Shirt',
                'description' => 'Comfortable cotton t-shirt',
                'base_price' => 29.99,
                'category' => $clothing,
                'stock' => 100,
            ],
            [
                'title' => 'Programming Book',
                'description' => 'Learn Laravel development',
                'base_price' => 49.99,
                'category' => $books,
                'stock' => 75,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(
                ['title' => $productData['title']],
                [
                    'vendor_id' => $vendor->id,
                    'slug' => \Illuminate\Support\Str::slug($productData['title']) . '-' . \Illuminate\Support\Str::random(6),
                    'description' => $productData['description'],
                    'base_price' => $productData['base_price'],
                    'status' => 'active',
                ]
            );

            $product->categories()->syncWithoutDetaching([$productData['category']->id]);

            // Create SKU
            $sku = Sku::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'sku_code' => 'SKU-' . strtoupper(\Illuminate\Support\Str::random(8)),
                    'price' => $productData['base_price'],
                    'active' => true,
                ]
            );

            // Create stock
            Stock::firstOrCreate(
                [
                    'sku_id' => $sku->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'on_hand' => $productData['stock'],
                    'reserved' => 0,
                ]
            );
        }

        // Create shipping zones and methods
        $zone = ShippingZone::firstOrCreate(
            ['name' => 'Domestic'],
            ['region' => 'US']
        );

        ShippingZoneRule::firstOrCreate(
            ['shipping_zone_id' => $zone->id, 'rule_type' => 'flat'],
            [
                'config' => [
                    'rate' => 1000,
                    'method' => 'standard',
                    'name' => 'Standard Shipping',
                ],
            ]
        );

        ShippingMethod::firstOrCreate(['name' => 'standard']);
        ShippingMethod::firstOrCreate(['name' => 'express']);
        ShippingMethod::firstOrCreate(['name' => 'overnight']);

        //call permissions seeder
        $this->call(PermissionsSeeder::class);

        $this->command->info('Database seeded successfully!');
    }
}

