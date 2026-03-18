<?php

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_customer_can_view_and_cancel_order(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-order@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-order@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);
        Mail::fake();

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $order = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertCreated()->json('order');

        $this->getJson('/api/orders')->assertOk();
        $this->postJson('/api/orders/' . $order['id'] . '/cancel')->assertOk();
    }
}
