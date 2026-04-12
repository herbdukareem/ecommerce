<?php

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use App\Models\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_customer_can_quote_shipping_and_place_order(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-checkout@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-checkout@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);
        Mail::fake();

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $this->postJson('/api/checkout/quote-shipping', [
            'address_id' => $address->id,
        ])->assertOk()->assertJsonStructure(['quotes']);

        $order = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertCreated()->assertJsonStructure(['order'])->json('order');

        $this->assertNotEmpty($order['delivery_snapshot']);
        $this->assertNotEmpty($order['delivery_address_snapshot']);
    }

    public function test_checkout_rejects_disabled_provider(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-checkout-provider@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-checkout-provider@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);
        Mail::fake();

        Sanctum::actingAs($customer);

        $this->getJson('/api/payments/gateways')->assertOk();

        PaymentGateway::query()->where('provider', 'paystack')->update([
            'is_enabled' => false,
            'is_visible' => true,
        ]);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertStatus(422);
    }

    public function test_checkout_requires_payment_gateway_selection(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-checkout-missing-provider@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-checkout-missing-provider@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);
        Mail::fake();

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'shipping_method' => 'standard',
        ])->assertStatus(422)->assertJsonValidationErrors(['payment_provider']);
    }
}
