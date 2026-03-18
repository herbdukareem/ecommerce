<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_customer_can_initialize_and_verify_payment(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-payment@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-payment@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        Sanctum::actingAs($customer);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.test/pay',
                    'access_code' => 'ACCESS_CODE',
                ],
            ], 200),
            '*/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                ],
            ], 200),
        ]);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $order = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertCreated()->json('order');

        $initialized = $this->postJson('/api/payments/orders/' . $order['id'] . '/initialize')
            ->assertOk()
            ->assertJsonStructure(['checkout' => ['reference']])
            ->json();

        $paymentId = $initialized['payment']['id'];
        $reference = $initialized['checkout']['reference'];

        $this->postJson('/api/payments/' . $paymentId . '/verify', [
            'reference' => $reference,
        ])->assertOk();

        $this->postJson('/api/payments/' . $paymentId . '/verify', [
            'reference' => $reference,
        ])->assertOk()->assertJsonPath('result.idempotent', true);
    }

    public function test_payment_webhook_is_accepted_and_idempotent(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-webhook@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-webhook@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        Sanctum::actingAs($customer);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.test/pay',
                    'access_code' => 'ACCESS_CODE',
                ],
            ], 200),
            '*/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                ],
            ], 200),
        ]);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $order = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertCreated()->json('order');

        $initialized = $this->postJson('/api/payments/orders/' . $order['id'] . '/initialize')
            ->assertOk()
            ->json();

        $reference = $initialized['checkout']['reference'];
        $paymentId = $initialized['payment']['id'];

        $payload = [
            'data' => [
                'reference' => $reference,
            ],
        ];

        $rawPayload = json_encode($payload);
        $signature = hash_hmac('sha512', $rawPayload, (string) config('services.paystack.secret_key'));

        $this->call(
            'POST',
            '/api/payments/webhook/paystack',
            [],
            [],
            [],
            [
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        )->assertStatus(202);

        $this->call(
            'POST',
            '/api/payments/webhook/paystack',
            [],
            [],
            [],
            [
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        )->assertStatus(202);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'paid',
            'transaction_id' => $reference,
        ]);
    }
}
