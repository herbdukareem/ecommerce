<?php

namespace Tests\Feature;

use App\Models\DeliveryPartner;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class OrderDeliveryWorkflowTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_admin_can_assign_partner_and_update_delivery_status(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-delivery@test.com');
        $customer = $this->makeUserWithRole('Customer', 'customer-delivery@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-delivery@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);
        $partner = DeliveryPartner::create([
            'name' => 'Rider One',
            'phone' => '08035555555',
            'status' => 'active',
        ]);

        Mail::fake();

        Sanctum::actingAs($customer);
        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $order = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertCreated()->json('order');

        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/orders/' . $order['id'] . '/delivery-assignment', [
            'delivery_partner_id' => $partner->id,
            'delivery_tracking_code' => 'TRK-001',
            'dispatch_note' => 'Handle with care',
        ])->assertOk()->assertJsonPath('order.delivery_status', 'assigned');

        $this->putJson('/api/admin/orders/' . $order['id'] . '/delivery-status', [
            'delivery_status' => 'packed',
        ])->assertOk()->assertJsonPath('order.delivery_status', 'packed');

        $this->putJson('/api/admin/orders/' . $order['id'] . '/delivery-status', [
            'delivery_status' => 'shipped',
        ])->assertOk();

        $this->putJson('/api/admin/orders/' . $order['id'] . '/delivery-status', [
            'delivery_status' => 'in_transit',
        ])->assertOk();

        $this->putJson('/api/admin/orders/' . $order['id'] . '/delivery-status', [
            'delivery_status' => 'delivered',
        ])->assertOk()->assertJsonPath('order.delivery_status', 'delivered');

        $this->assertNotNull(Order::find($order['id'])->delivered_at);
    }

    public function test_non_admin_cannot_assign_delivery_partner(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-no-admin@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-no-admin@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);
        $partner = DeliveryPartner::create([
            'name' => 'Rider Two',
            'phone' => '08036666666',
            'status' => 'active',
        ]);

        Mail::fake();

        Sanctum::actingAs($customer);
        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $order = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertCreated()->json('order');

        $this->putJson('/api/admin/orders/' . $order['id'] . '/delivery-assignment', [
            'delivery_partner_id' => $partner->id,
        ])->assertForbidden();
    }

    public function test_customer_can_view_delivery_details(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-view-delivery@test.com');
        $customer = $this->makeUserWithRole('Customer', 'customer-view-delivery@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-view-delivery@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);
        $partner = DeliveryPartner::create([
            'name' => 'Rider Three',
            'phone' => '08037777777',
            'status' => 'active',
        ]);

        Mail::fake();

        Sanctum::actingAs($customer);
        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $order = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertCreated()->json('order');

        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/orders/' . $order['id'] . '/delivery-assignment', [
            'delivery_partner_id' => $partner->id,
            'delivery_tracking_code' => 'TRK-002',
        ])->assertOk();

        Sanctum::actingAs($customer);
        $this->getJson('/api/orders/' . $order['id'])
            ->assertOk()
            ->assertJsonPath('delivery_partner_id', $partner->id)
            ->assertJsonPath('delivery_tracking_code', 'TRK-002');
    }
}
