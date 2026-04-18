<?php

namespace Tests\Feature;

use App\Models\OrderItem;
use App\Models\Sku;
use App\Models\SkuImage;
use App\Models\Stock;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class ProductOptionsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_admin_can_create_optioned_product_with_multiple_options(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-options-create@test.com');
        $category = \App\Models\Category::create(['name' => 'Food']);

        Sanctum::actingAs($admin);

        $response = $this->post('/api/admin/products', [
            'name' => 'Big Bull Rice',
            'description' => 'Rice pack options',
            'price' => 2000,
            'status' => 'active',
            'category_id' => $category->id,
            'has_options' => true,
            'variants' => json_encode([
                [
                    'label' => '1kg (mudu)',
                    'sku' => 'RICE-1KG',
                    'price' => 2000,
                    'stock_quantity' => 10,
                    'sort_order' => 0,
                    'is_active' => true,
                ],
                [
                    'label' => '2kg (mudu)',
                    'sku' => 'RICE-2KG',
                    'price' => 5000,
                    'stock_quantity' => 8,
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ]),
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $productId = (int) $response->json('product.id');

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'has_options' => 1,
        ]);

        $this->assertDatabaseHas('skus', [
            'product_id' => $productId,
            'option_label' => '1kg (mudu)',
            'price' => 2000,
        ]);

        $this->assertDatabaseHas('skus', [
            'product_id' => $productId,
            'option_label' => '2kg (mudu)',
            'price' => 5000,
        ]);
    }

    public function test_admin_can_update_and_deactivate_product_option(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-options-update@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-options-update@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        Sanctum::actingAs($admin);

        $this->put('/api/admin/products/' . $commerce['product']->id, [
            'name' => $commerce['product']->title,
            'status' => 'active',
            'price' => 2000,
            'category_id' => $commerce['category']->id,
            'has_options' => true,
            'variants' => json_encode([
                [
                    'label' => '1kg (mudu)',
                    'sku' => 'RICE-1KG',
                    'price' => 2100,
                    'stock_quantity' => 10,
                    'sort_order' => 0,
                    'is_active' => true,
                ],
                [
                    'label' => '2kg (mudu)',
                    'sku' => 'RICE-2KG',
                    'price' => 5200,
                    'stock_quantity' => 6,
                    'sort_order' => 1,
                    'is_active' => false,
                ],
            ]),
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $this->assertDatabaseHas('skus', [
            'product_id' => $commerce['product']->id,
            'option_label' => '2kg (mudu)',
            'active' => 0,
        ]);
    }

    public function test_simple_product_can_be_added_without_option_selection(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-simple-product@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-simple-product@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(1, 'items');
    }

    public function test_optioned_product_requires_option_selection(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-option-required@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-option-required@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'quantity' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Please select a product option before adding to cart');
    }

    public function test_optioned_product_adds_selected_option_and_uses_option_price(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-option-add@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-option-add@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $selectedOption = $commerce['options'][1]; // 2kg (mudu) @ 5000

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'sku_id' => $selectedOption->id,
            'quantity' => 2,
        ])->assertCreated();

        $cart = $this->getJson('/api/cart')->assertOk()->json();

        $this->assertEquals('2kg (mudu)', $cart['items'][0]['option_label']);
        $this->assertEquals(5000.0, (float) $cart['items'][0]['price']);
        $this->assertEquals(10000.0, (float) $cart['subtotal']);
    }

    public function test_same_product_with_different_options_creates_separate_cart_lines(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-option-lines@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-option-lines@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'sku_id' => $commerce['options'][0]->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'sku_id' => $commerce['options'][1]->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(2, 'items');
    }

    public function test_optioned_product_selected_sku_accepts_quantity_and_merges_same_line(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-option-qty-merge@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-option-qty-merge@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $selectedOption = $commerce['options'][0];

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'sku_id' => $selectedOption->id,
            'quantity' => 2,
        ])->assertCreated();

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'sku_id' => $selectedOption->id,
            'quantity' => 3,
        ])->assertCreated();

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.quantity', 5)
            ->assertJsonPath('items.0.option_label', '1kg (mudu)');
    }

    public function test_inactive_or_out_of_stock_option_cannot_be_added(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-option-invalid@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-option-invalid@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        $inactive = $commerce['options'][1];
        $inactive->update(['active' => false]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'sku_id' => $inactive->id,
            'quantity' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Selected option is inactive');

        $active = $commerce['options'][0];
        Stock::query()->where('sku_id', $active->id)->update(['on_hand' => 0, 'reserved' => 0]);
        $active->update(['stock_quantity' => 0]);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'sku_id' => $active->id,
            'quantity' => 1,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Insufficient stock available');
    }

    public function test_selected_option_carries_to_order_item_snapshot_and_inventory_deduction(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-option-order@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-option-order@test.com');
        $admin = $this->makeUserWithRole('Admin', 'admin-option-order@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/inventory/add-stock', [
            'sku_id' => $commerce['options'][0]->id,
            'quantity' => 10,
            'cost_price' => 1000,
            'selling_price' => 2000,
            'batch_reference' => 'OPT-1KG-BATCH',
        ])->assertCreated();

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'sku_id' => $commerce['options'][0]->id,
            'quantity' => 2,
        ])->assertCreated();

        $orderPayload = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_provider' => 'paystack',
        ])->assertCreated()->json('order');

        $orderItem = OrderItem::query()->where('order_id', $orderPayload['id'])->first();
        $this->assertNotNull($orderItem);
        $this->assertEquals('1kg (mudu)', $orderItem->option_label_snapshot);
        $this->assertEquals($commerce['product']->title, $orderItem->product_name_snapshot);

        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/orders/' . $orderPayload['id'] . '/status', [
            'status' => 'delivered',
        ])->assertOk();

        $updatedStock = Stock::query()->where('sku_id', $commerce['options'][0]->id)->first();
        $this->assertNotNull($updatedStock);
        $this->assertLessThan(20, (int) $updatedStock->on_hand);

        $this->assertDatabaseHas('inventory_ledger_entries', [
            'product_option_id' => $commerce['options'][0]->id,
        ]);
    }

    public function test_admin_can_manage_sku_images_for_product_option(): void
    {
        Storage::fake('public');

        $admin = $this->makeUserWithRole('Admin', 'admin-option-images@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-option-images@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        Sanctum::actingAs($admin);

        $sku = $commerce['options'][0];

        $upload = $this->post('/api/admin/products/' . $commerce['product']->id . '/skus/' . $sku->id . '/images', [
            'images' => [
                UploadedFile::fake()->image('option-front.jpg'),
                UploadedFile::fake()->image('option-side.jpg'),
            ],
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $images = $upload->json('images');
        $this->assertCount(2, $images);

        $firstId = $images[0]['id'];
        $secondId = $images[1]['id'];

        $this->putJson('/api/admin/products/' . $commerce['product']->id . '/skus/' . $sku->id . '/images/order', [
            'image_ids' => [$secondId, $firstId],
        ])->assertOk();

        $this->putJson('/api/admin/products/' . $commerce['product']->id . '/skus/' . $sku->id . '/images/' . $secondId . '/primary')
            ->assertOk();

        $this->deleteJson('/api/admin/products/' . $commerce['product']->id . '/skus/' . $sku->id . '/images/' . $firstId)
            ->assertOk();

        $this->assertDatabaseMissing('sku_images', ['id' => $firstId]);
        $this->assertDatabaseHas('sku_images', ['id' => $secondId, 'is_primary' => true]);
    }

    public function test_catalog_product_payload_includes_sku_images_with_primary_url(): void
    {
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-option-catalog-media@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        $sku = $commerce['options'][0];

        SkuImage::create([
            'sku_id' => $sku->id,
            'image_path' => 'skus/option-primary.jpg',
            'is_primary' => true,
            'sort_order' => 0,
            'created_by' => $vendor->id,
        ]);

        SkuImage::create([
            'sku_id' => $sku->id,
            'image_path' => 'skus/option-secondary.jpg',
            'is_primary' => false,
            'sort_order' => 1,
            'created_by' => $vendor->id,
        ]);

        $payload = $this->getJson('/api/products/' . $commerce['product']->slug)
            ->assertOk()
            ->json();

        $matchedSku = collect($payload['skus'] ?? [])->firstWhere('id', $sku->id);

        $this->assertNotNull($matchedSku);
        $this->assertEquals('/storage/skus/option-primary.jpg', $matchedSku['primary_image_url'] ?? null);
        $this->assertCount(2, $matchedSku['images'] ?? []);
    }
}
