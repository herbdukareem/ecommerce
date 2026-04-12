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
use App\Models\DeliveryPartner;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed permission matrix first so role records are present before user assignment.
        $this->call(PermissionsSeeder::class);
        $this->call(LocationSeeder::class);
        $this->call(DispatchAndOperationsSeeder::class);

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'sanctum']);
        $vendorRole = Role::firstOrCreate(['name' => 'Vendor', 'guard_name' => 'sanctum']);
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'sanctum']);

        // Create demo admin user and enforce demo credential consistency for local/dev seed runs.
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );
        $admin->syncRoles([$adminRole]);

        // Create vendor user
        $vendor = User::updateOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name' => 'Vendor User',
                'password' => Hash::make('password'),
            ]
        );
        $vendor->syncRoles([$vendorRole]);

        // Create customer user
        $customer = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Customer User',
                'password' => Hash::make('password'),
            ]
        );
        $customer->syncRoles([$customerRole]);

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
        $standardMethod = ShippingMethod::updateOrCreate(
            ['code' => 'standard'],
            [
                'name' => 'Standard Delivery',
                'description' => '2-4 business days within major cities',
                'base_fee' => 2500,
                'per_kg_surcharge' => 300,
                'supports_cod' => true,
                'active' => true,
            ]
        );

        $expressMethod = ShippingMethod::updateOrCreate(
            ['code' => 'express'],
            [
                'name' => 'Express Delivery',
                'description' => 'Same or next day in selected areas',
                'base_fee' => 4500,
                'per_kg_surcharge' => 500,
                'express_surcharge' => 1000,
                'supports_cod' => false,
                'active' => true,
            ]
        );

        $pickupMethod = ShippingMethod::updateOrCreate(
            ['code' => 'pickup'],
            [
                'name' => 'Pickup Station',
                'description' => 'Collect from pickup station',
                'base_fee' => 0,
                'is_pickup' => true,
                'supports_cod' => false,
                'active' => true,
            ]
        );

        $lagosZone = ShippingZone::updateOrCreate(
            ['name' => 'Lagos Metro'],
            [
                'region' => 'Lagos',
                'coverage_states' => ['lagos'],
                'coverage_cities' => ['ikeja', 'lekki', 'yaba', 'surulere'],
                'default_fee' => 2000,
                'active' => true,
            ]
        );

        $fallbackZone = ShippingZone::updateOrCreate(
            ['name' => 'Nationwide Fallback'],
            [
                'region' => 'Nigeria',
                'default_fee' => 4500,
                'is_fallback' => true,
                'active' => true,
            ]
        );

        ShippingZoneRule::updateOrCreate(
            ['shipping_zone_id' => $lagosZone->id, 'shipping_method_id' => $standardMethod->id, 'rule_type' => 'flat'],
            [
                'config' => [
                    'rate' => 2000,
                    'method' => 'standard',
                    'name' => 'Lagos Standard Delivery',
                    'cod_available' => true,
                ],
                'priority' => 10,
                'active' => true,
            ]
        );

        ShippingZoneRule::updateOrCreate(
            ['shipping_zone_id' => $lagosZone->id, 'shipping_method_id' => $expressMethod->id, 'rule_type' => 'flat'],
            [
                'config' => [
                    'rate' => 3500,
                    'method' => 'express',
                    'name' => 'Lagos Express Delivery',
                    'cod_available' => false,
                ],
                'priority' => 20,
                'active' => true,
            ]
        );

        ShippingZoneRule::updateOrCreate(
            ['shipping_zone_id' => $fallbackZone->id, 'shipping_method_id' => $standardMethod->id, 'rule_type' => 'flat'],
            [
                'config' => [
                    'rate' => 4500,
                    'method' => 'standard',
                    'name' => 'Outside Lagos Standard Delivery',
                    'cod_available' => true,
                ],
                'priority' => 100,
                'active' => true,
            ]
        );

        ShippingZoneRule::updateOrCreate(
            ['shipping_zone_id' => $fallbackZone->id, 'shipping_method_id' => $pickupMethod->id, 'rule_type' => 'flat'],
            [
                'config' => [
                    'rate' => 0,
                    'method' => 'pickup',
                    'name' => 'Pickup Station',
                    'cod_available' => false,
                ],
                'priority' => 200,
                'active' => true,
            ]
        );

        DeliveryPartner::updateOrCreate(
            ['name' => 'Lagos Dispatch Rider'],
            [
                'phone' => '08030000000',
                'email' => 'dispatch@example.com',
                'company_name' => 'Ashlab Local Dispatch',
                'coverage_states' => ['lagos'],
                'coverage_cities' => ['ikeja', 'lekki', 'yaba', 'surulere'],
                'coverage_areas' => ['allen', 'admiralty', 'tejuosho'],
                'pricing_notes' => 'Manual dispatch rates apply for oversized orders.',
                'status' => 'active',
                'vehicle_type' => 'motorbike',
            ]
        );

        $this->command->info('Database seeded successfully!');
    }
}

