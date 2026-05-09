<?php

namespace Tests\Feature;

use App\Models\DispatchTimeSlot;
use App\Models\DeliveryPartner;
use App\Models\DispatchAssignment;
use App\Models\DispatchRider;
use App\Models\OperationArea;
use App\Models\OperationCity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductImage;
use App\Models\Sku;
use App\Models\Stock;
use App\Services\CurrencyFormatter;
use Carbon\Carbon;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class OperationsAndInventoryTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    public function test_checkout_operational_metadata_and_admin_masters(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'ops-admin@test.com');
        Sanctum::actingAs($admin);

        $city = $this->postJson('/api/admin/operation-cities', [
            'name' => 'Abuja',
            'code' => 'abuja',
            'status' => 'active',
            'sort_order' => 1,
        ])->assertCreated()->json('city');

        $area = $this->postJson('/api/admin/operation-areas', [
            'city_id' => $city['id'],
            'name' => 'Maitama',
            'delivery_fee' => 2500,
            'status' => 'active',
            'sort_order' => 1,
        ])->assertCreated()->json('area');

        $slot = $this->postJson('/api/admin/dispatch-time-slots', [
            'label' => 'Morning',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'active',
            'sort_order' => 1,
        ])->assertCreated()->json('slot');

        $this->getJson('/api/checkout/cities')
            ->assertOk()
            ->assertJsonFragment(['id' => $city['id'], 'name' => 'Abuja']);

        $this->getJson('/api/checkout/areas?city_id=' . $city['id'])
            ->assertOk()
            ->assertJsonFragment(['id' => $area['id'], 'name' => 'Maitama']);

        $this->getJson('/api/checkout/dispatch-time-slots')
            ->assertOk()
            ->assertJsonFragment(['id' => $slot['id'], 'label' => 'Morning']);
    }

    public function test_admin_web_guard_can_access_permission_protected_operations_endpoint(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'ops-admin-web-guard@test.com');

        $this->actingAs($admin, 'web');

        $this->postJson('/api/admin/operation-cities', [
            'name' => 'Kano',
            'code' => 'kano',
            'status' => 'active',
            'sort_order' => 3,
        ])->assertCreated();
    }

    public function test_admin_can_create_order_for_customer(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-order-create@test.com');
        $customer = $this->makeUserWithRole('Customer', 'customer-order-create@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-order-create@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        [$city, $area, $slot] = $this->createOpsMasters();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/orders/customers')
            ->assertOk()
            ->assertJsonFragment(['id' => $customer->id]);

        $this->getJson('/api/admin/orders/products')
            ->assertOk()
            ->assertJsonFragment(['sku_id' => $commerce['sku']->id]);

        $response = $this->postJson('/api/admin/orders', [
            'customer_id' => $customer->id,
            'city_id' => $city->id,
            'area_id' => $area->id,
            'dispatch_time_slot_id' => $slot->id,
            'payment_mode' => 'cash',
            'items' => [
                [
                    'sku_id' => $commerce['sku']->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertCreated();

        $orderId = $response->json('order.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $customer->id,
            'created_by_admin_id' => $admin->id,
            'city_id' => $city->id,
            'area_id' => $area->id,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'sku_id' => $commerce['sku']->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_payload_includes_product_images(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-cart-images@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-cart-images@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        ProductImage::create([
            'product_id' => $commerce['product']->id,
            'image_path' => 'products/test-image.jpg',
            'image_url' => '/storage/products/test-image.jpg',
            'is_primary' => true,
            'order' => 0,
        ]);
        $commerce['product']->update(['image' => '/storage/products/test-image.jpg']);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'sku_id' => $commerce['sku']->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('items.0.product_image', '/storage/products/test-image.jpg')
            ->assertJsonPath('items.0.product_images.0.image_url', '/storage/products/test-image.jpg');
    }

    public function test_admin_can_manage_product_images(): void
    {
        Storage::fake('public');

        $admin = $this->makeUserWithRole('Admin', 'admin-product-images@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-product-images@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($admin);

        $upload = $this->post('/api/admin/products/' . $commerce['product']->id . '/images', [
            'images' => [
                UploadedFile::fake()->image('first.jpg'),
                UploadedFile::fake()->image('second.jpg'),
            ],
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $images = $upload->json('images');
        $this->assertCount(2, $images);

        $firstId = $images[0]['id'];
        $secondId = $images[1]['id'];

        $this->putJson('/api/admin/products/' . $commerce['product']->id . '/images/order', [
            'image_ids' => [$secondId, $firstId],
        ])->assertOk();

        $this->putJson('/api/admin/products/' . $commerce['product']->id . '/images/' . $secondId . '/primary')
            ->assertOk();

        $this->deleteJson('/api/admin/products/' . $commerce['product']->id . '/images/' . $firstId)
            ->assertOk();

        $this->assertDatabaseMissing('product_images', ['id' => $firstId]);
        $this->assertDatabaseHas('product_images', ['id' => $secondId, 'is_primary' => true]);
    }

    public function test_inventory_endpoints_add_stock_update_expiry_and_ledger(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-inventory@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-inventory@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/inventory/skus')
            ->assertOk()
            ->assertJsonFragment(['sku_id' => $commerce['sku']->id]);

        $expiry = Carbon::today()->addDays(10)->toDateString();

        $batchResponse = $this->postJson('/api/admin/inventory/add-stock', [
            'sku_id' => $commerce['sku']->id,
            'quantity' => 5,
            'cost_price' => 45,
            'selling_price' => 80,
            'expiry_date' => $expiry,
            'batch_reference' => 'BATCH-001',
            'source_type' => 'supplier',
            'note' => 'Initial stock intake',
        ])->assertCreated();

        $batchId = $batchResponse->json('batch.id');

        $this->getJson('/api/admin/inventory/ledger')
            ->assertOk()
            ->assertJsonFragment(['movement_type' => 'stock_addition']);

        $this->putJson('/api/admin/inventory/batches/' . $batchId . '/expiry', [
            'expiry_date' => Carbon::today()->addDays(14)->toDateString(),
        ])->assertOk();

        $this->getJson('/api/admin/inventory/expiry-alerts?bucket=30_days')
            ->assertOk()
            ->assertJsonFragment(['id' => $batchId]);
    }

    public function test_inventory_selling_price_overrides_storefront_cart_and_order_prices(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-inventory-price@test.com');
        $customer = $this->makeUserWithRole('Customer', 'customer-inventory-price@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-inventory-price@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/inventory/add-stock', [
            'sku_id' => $commerce['sku']->id,
            'quantity' => 5,
            'cost_price' => 45,
            'selling_price' => 80,
        ])->assertCreated();

        $this->assertSame(80.0, (float) $commerce['sku']->fresh()->price);
        $this->assertSame(80.0, (float) $commerce['product']->fresh()->base_price);
        $this->assertSame(80.0, (float) $commerce['product']->fresh()->price);

        $catalogProducts = $this->getJson('/api/products?per_page=10')
            ->assertOk()
            ->json('data');
        $catalogProduct = collect($catalogProducts)->firstWhere('id', $commerce['product']->id);

        $this->assertNotNull($catalogProduct);
        $this->assertSame(80.0, (float) $catalogProduct['base_price']);
        $this->assertSame(80.0, (float) $catalogProduct['display_price']);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'sku_id' => $commerce['sku']->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('items.0.price', 80)
            ->assertJsonPath('items.0.subtotal', 80);

        [$city, $area, $slot] = $this->createOpsMasters();

        $orderId = $this->postJson('/api/checkout/place-order', [
            'city_id' => $city->id,
            'area_id' => $area->id,
            'dispatch_time_slot_id' => $slot->id,
            'payment_mode' => 'paystack',
        ])->assertCreated()->json('order.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'subtotal' => 80,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'sku_id' => $commerce['sku']->id,
            'price_snapshot' => 80,
        ]);
    }

    public function test_admin_products_index_uses_central_inventory_available_stock(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-products-stock@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-products-stock@test.com');

        $simple = $this->makeProductWithStock($vendor);
        $optioned = $this->makeOptionedProductWithStock($vendor);

        // Simple product stock truth source: 14 on hand, 4 reserved => 10 available.
        $simple['sku']->update(['stock_quantity' => 99999]);
        Stock::query()->where('sku_id', $simple['sku']->id)->update([
            'on_hand' => 14,
            'reserved' => 4,
        ]);

        // Optioned product stock truth source: first 18-5=13, second 6-6=0 => total 13.
        $firstOption = $optioned['options'][0];
        $secondOption = $optioned['options'][1];

        $firstOption->update(['stock_quantity' => 50000]);
        $secondOption->update(['stock_quantity' => 50000]);

        Stock::query()->where('sku_id', $firstOption->id)->update([
            'on_hand' => 18,
            'reserved' => 5,
        ]);
        Stock::query()->where('sku_id', $secondOption->id)->update([
            'on_hand' => 6,
            'reserved' => 6,
        ]);

        // Inactive SKU should be ignored by products index stock aggregation.
        $inactiveSku = Sku::create([
            'product_id' => $optioned['product']->id,
            'sku_code' => 'INACTIVE-' . strtoupper(uniqid()),
            'price' => 7000,
            'active' => false,
            'stock_quantity' => 1000,
        ]);
        Stock::create([
            'sku_id' => $inactiveSku->id,
            'warehouse_id' => $optioned['warehouse']->id,
            'on_hand' => 500,
            'reserved' => 0,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/products')
            ->assertOk()
            ->json('data');

        $simpleRow = collect($response)->firstWhere('id', $simple['product']->id);
        $optionedRow = collect($response)->firstWhere('id', $optioned['product']->id);

        $this->assertNotNull($simpleRow);
        $this->assertNotNull($optionedRow);

        $this->assertSame(10, (int) ($simpleRow['available_stock'] ?? -1));
        $this->assertSame(13, (int) ($optionedRow['available_stock'] ?? -1));
        $this->assertSame(2, (int) ($optionedRow['active_sku_count'] ?? -1));
    }

    public function test_admin_product_detail_exposes_sku_available_stock_from_stocks(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-product-detail-stock@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-product-detail-stock@test.com');
        $commerce = $this->makeOptionedProductWithStock($vendor);

        Stock::query()->where('sku_id', $commerce['options'][0]->id)->update(['on_hand' => 12, 'reserved' => 3]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/products/' . $commerce['product']->id)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $commerce['options'][0]->id,
                'available_stock' => 9,
            ]);
    }

    public function test_currency_formatter_uses_settings(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'currency_symbol'], ['value' => 'USD ', 'updated_at' => now()]);
        DB::table('settings')->updateOrInsert(['key' => 'currency'], ['value' => 'USD', 'updated_at' => now()]);

        $this->assertSame('USD 1,234.50', app(CurrencyFormatter::class)->format(1234.5));
    }

    public function test_admin_can_download_terminal_receipt(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-receipt@test.com');
        $customer = $this->makeUserWithRole('Customer', 'customer-receipt@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-receipt@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        $order = Order::create([
            'user_id' => $customer->id,
            'created_by_admin_id' => $admin->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_mode' => 'cash',
            'subtotal' => 200,
            'delivery_fee' => 50,
            'tax' => 0,
            'total' => 250,
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'sku_id' => $commerce['sku']->id,
            'quantity' => 2,
            'price_snapshot' => 100,
            'weight_snapshot' => 1,
            'length_snapshot' => 1,
            'width_snapshot' => 1,
            'height_snapshot' => 1,
        ]);

        Sanctum::actingAs($admin);

        $this->get('/api/admin/orders/' . $order->id . '/terminal-receipt', ['Accept' => 'text/html'])
            ->assertOk()
            ->assertHeader('content-disposition')
            ->assertSee('Order #' . $order->id, false)
            ->assertSee('Thank you for shopping with us.', false);
    }

    public function test_dispatch_rider_creation_assignment_and_rider_restrictions(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-dispatch@test.com');
        $customer = $this->makeUserWithRole('Customer', 'customer-dispatch@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-dispatch@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $partner = DeliveryPartner::create([
            'name' => 'Test Logistics',
            'phone' => '08030000000',
            'coverage_cities' => ['lagos'],
            'status' => 'active',
        ]);

        Sanctum::actingAs($admin);

        $riderResponse = $this->postJson('/api/admin/dispatch-riders', [
            'name' => 'Rider One',
            'email' => 'rider-one@test.com',
            'phone' => '08030000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'delivery_partner_id' => $partner->id,
            'vehicle_type' => 'motorbike',
            'status' => 'active',
        ])->assertCreated();

        $secondRiderResponse = $this->postJson('/api/admin/dispatch-riders', [
            'name' => 'Rider Two',
            'email' => 'rider-two@test.com',
            'phone' => '08030000002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'vehicle_type' => 'motorbike',
            'status' => 'active',
        ])->assertCreated();

        $order = Order::create([
            'user_id' => $customer->id,
            'status' => 'ready_for_dispatch',
            'payment_status' => 'paid',
            'subtotal' => 100,
            'total' => 100,
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'sku_id' => $commerce['sku']->id,
            'quantity' => 1,
            'price_snapshot' => 100,
            'weight_snapshot' => 1,
            'length_snapshot' => 1,
            'width_snapshot' => 1,
            'height_snapshot' => 1,
        ]);

        $assignment = $this->putJson('/api/admin/orders/' . $order->id . '/dispatch-rider', [
            'dispatch_rider_id' => $riderResponse->json('rider.id'),
        ])->assertOk()->json('assignment');

        $riderTwoUser = DispatchRider::find($secondRiderResponse->json('rider.id'))->user;
        Sanctum::actingAs($riderTwoUser);
        $this->putJson('/api/dispatch/assignments/' . $assignment['id'], ['status' => 'accepted'])
            ->assertNotFound();

        $riderOneUser = DispatchRider::find($riderResponse->json('rider.id'))->user;
        Sanctum::actingAs($riderOneUser);
        $this->putJson('/api/dispatch/assignments/' . $assignment['id'], [
            'status' => 'rejected',
            'rejection_reason' => 'Vehicle fault',
        ])->assertOk();

        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/orders/' . $order->id . '/dispatch-rider', [
            'dispatch_rider_id' => $secondRiderResponse->json('rider.id'),
        ])->assertOk();

        $this->assertDatabaseHas('dispatch_assignments', [
            'id' => $assignment['id'],
            'status' => 'rejected',
            'rejection_reason' => 'Vehicle fault',
        ]);
        $this->assertSame(2, DispatchAssignment::where('order_id', $order->id)->count());
    }

    public function test_profit_margin_report_returns_summary(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-profit@test.com');
        $customer = $this->makeUserWithRole('Customer', 'customer-profit@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-profit@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        $order = Order::create([
            'user_id' => $customer->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'subtotal' => 300,
            'shipping_cost' => 0,
            'delivery_fee' => 0,
            'tax' => 0,
            'total' => 300,
            'placed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'sku_id' => $commerce['sku']->id,
            'quantity' => 2,
            'price_snapshot' => 150,
            'unit_cost_at_sale' => 90,
            'total_cost_at_sale' => 180,
            'unit_price_at_sale' => 150,
            'total_price_at_sale' => 300,
            'weight_snapshot' => 1,
            'length_snapshot' => 1,
            'width_snapshot' => 1,
            'height_snapshot' => 1,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/reports/profit-margins')
            ->assertOk();

        $this->assertEquals(300.0, (float) $response->json('summary.revenue'));
        $this->assertEquals(180.0, (float) $response->json('summary.total_cost'));
        $this->assertEquals(120.0, (float) $response->json('summary.gross_profit'));
        $this->assertGreaterThanOrEqual(1, count($response->json('orders') ?? []));
    }

    private function createOpsMasters(): array
    {
        $city = OperationCity::create([
            'name' => 'Lagos',
            'code' => 'lagos',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $area = OperationArea::create([
            'city_id' => $city->id,
            'name' => 'Yaba',
            'delivery_fee' => 1800,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $slot = DispatchTimeSlot::create([
            'label' => 'Midday',
            'start_time' => '12:00',
            'end_time' => '14:00',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        return [$city, $area, $slot];
    }
}
