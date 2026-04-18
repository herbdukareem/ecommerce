<?php

namespace Tests\Feature\Support;

use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sku;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

trait CreatesCommerceData
{
    protected function makeUserWithRole(string $roleName, string $email): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);

        $user = User::create([
            'name' => $roleName . ' User',
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function makeProductWithStock(User $vendor): array
    {
        $category = Category::create(['name' => 'Test Category']);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'title' => 'Test Product',
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'description' => 'Test product description',
            'base_price' => 100,
            'price' => 100,
            'status' => 'active',
        ]);

        $product->categories()->attach($category->id);

        $warehouse = Warehouse::firstOrCreate(
            ['vendor_id' => $vendor->id, 'name' => 'Default Warehouse'],
            ['location_id' => null]
        );

        $sku = Sku::create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-' . strtoupper(uniqid()),
            'price' => 100,
            'weight' => 1,
            'length' => 1,
            'width' => 1,
            'height' => 1,
            'active' => true,
            'stock_quantity' => 20,
        ]);

        Stock::updateOrCreate(
            ['sku_id' => $sku->id, 'warehouse_id' => $warehouse->id],
            ['on_hand' => 20, 'reserved' => 0]
        );

        return compact('category', 'product', 'sku', 'warehouse');
    }

    protected function makeAddress(User $customer): Address
    {
        return Address::create([
            'user_id' => $customer->id,
            'full_name' => 'John Doe',
            'name' => 'John Doe',
            'phone' => '08000000000',
            'email' => 'john.doe@example.com',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Ikeja',
            'area_or_district' => 'Allen',
            'address_line_1' => '12 Main Street',
            'address_line_2' => 'Suite 3',
            'landmark' => 'Near City Mall',
            'postal_code' => '100001',
            'delivery_note' => 'Call when arriving at gate',
            'line1' => '12 Main Street',
            'line2' => 'Suite 3',
            'zip' => '100001',
            'is_default' => true,
        ]);
    }

    protected function makeOptionedProductWithStock(User $vendor): array
    {
        $commerce = $this->makeProductWithStock($vendor);
        $product = $commerce['product'];
        $warehouse = $commerce['warehouse'];

        $product->update([
            'has_options' => true,
            'base_price' => 2000,
            'price' => 2000,
        ]);

        $first = $commerce['sku'];
        $first->update([
            'option_label' => '1kg (mudu)',
            'price' => 2000,
            'stock_quantity' => 10,
            'sort_order' => 0,
            'active' => true,
            'attributes' => ['name' => '1kg (mudu)'],
        ]);
        Stock::updateOrCreate(
            ['sku_id' => $first->id, 'warehouse_id' => $warehouse->id],
            ['on_hand' => 10, 'reserved' => 0]
        );

        $second = Sku::create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-' . strtoupper(uniqid()),
            'option_label' => '2kg (mudu)',
            'price' => 5000,
            'weight' => 2,
            'active' => true,
            'stock_quantity' => 8,
            'sort_order' => 1,
            'attributes' => ['name' => '2kg (mudu)'],
        ]);
        Stock::updateOrCreate(
            ['sku_id' => $second->id, 'warehouse_id' => $warehouse->id],
            ['on_hand' => 8, 'reserved' => 0]
        );

        return [
            'category' => $commerce['category'],
            'product' => $product->fresh(),
            'warehouse' => $warehouse,
            'options' => [$first->fresh(), $second->fresh()],
        ];
    }
}
