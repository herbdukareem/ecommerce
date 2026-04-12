<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
use Illuminate\Http\Client\Request as HttpRequest;
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
        config([
            'services.paystack.secret_key' => 'sk_test_webhook_signature',
            'services.paystack.webhook_secret' => null,
        ]);

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

    public function test_initialize_uses_database_paystack_secret_in_selected_mode(): void
    {
        config([
            'services.paystack.secret_key' => null,
            'services.paystack.webhook_secret' => null,
        ]);

        $customer = $this->makeUserWithRole('Customer', 'customer-db-secret@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-db-secret@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        $paystack = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $paystack->mode = 'sandbox';
        $paystack->is_enabled = true;
        $paystack->is_visible = true;
        $paystack->extra_config = [
            'credentials' => [
                'sandbox' => [
                    'secret_key' => 'sk_test_db_for_initialize',
                    'public_key' => 'pk_test_db_for_initialize',
                ],
            ],
        ];
        $paystack->save();

        Sanctum::actingAs($customer);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.test/pay',
                    'access_code' => 'ACCESS_CODE',
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

        $this->postJson('/api/payments/orders/' . $order['id'] . '/initialize')->assertOk();

        Http::assertSent(function (HttpRequest $request) {
            return str_contains($request->url(), '/transaction/initialize')
                && $request->hasHeader('Authorization', 'Bearer sk_test_db_for_initialize');
        });
    }

    public function test_checkout_hides_paystack_when_selected_mode_is_incomplete(): void
    {
        config([
            'services.paystack.secret_key' => null,
            'services.paystack.webhook_secret' => null,
        ]);

        PaymentGateway::query()->where('provider', 'paystack')->update([
            'mode' => 'live',
            'is_enabled' => true,
            'is_visible' => true,
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'secret_key' => 'sk_test_configured_but_not_active_mode',
                    ],
                    'live' => [
                        'secret_key' => null,
                    ],
                ],
            ],
        ]);

        $providers = collect($this->getJson('/api/payments/gateways')->assertOk()->json('gateways'))
            ->pluck('provider')
            ->all();

        $this->assertNotContains('paystack', $providers);
    }

    public function test_place_order_persists_selected_paystack_provider_and_initializes_checkout(): void
    {
        config([
            'services.paystack.secret_key' => null,
            'services.paystack.webhook_secret' => null,
        ]);

        PaymentGateway::query()->where('provider', 'paystack')->update([
            'mode' => 'sandbox',
            'is_enabled' => true,
            'is_visible' => true,
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'secret_key' => 'sk_test_place_order_paystack',
                        'public_key' => 'pk_test_place_order_paystack',
                    ],
                ],
            ],
        ]);

        $customer = $this->makeUserWithRole('Customer', 'customer-place-order-paystack@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-place-order-paystack@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        Sanctum::actingAs($customer);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.test/paystack-auth',
                    'access_code' => 'ACCESS_CODE_PAYSTACK',
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
            ->assertJsonPath('payment_flow', 'popup')
            ->assertJsonPath('checkout.provider', 'paystack')
            ->assertJsonPath('checkout.checkout_url', 'https://checkout.test/paystack-auth')
            ->assertJsonPath('checkout.public_key', 'pk_test_place_order_paystack')
            ->assertJsonPath('checkout.currency', 'NGN')
            ->json();

        $this->assertSame('paystack', data_get($initialized, 'payment.method'));
        $this->assertSame('paystack', data_get($initialized, 'payment.gateway_response.provider'));
        $this->assertNotSame('dummy', data_get($initialized, 'payment.gateway_response.provider'));
    }

    public function test_initialize_returns_clear_error_when_paystack_initialization_fails(): void
    {
        config([
            'services.paystack.secret_key' => null,
            'services.paystack.webhook_secret' => null,
        ]);

        PaymentGateway::query()->where('provider', 'paystack')->update([
            'mode' => 'sandbox',
            'is_enabled' => true,
            'is_visible' => true,
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'secret_key' => 'sk_test_failure_case',
                    ],
                ],
            ],
        ]);

        $customer = $this->makeUserWithRole('Customer', 'customer-init-failure@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-init-failure@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        Sanctum::actingAs($customer);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => false,
                'message' => 'gateway down',
            ], 500),
        ]);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $order = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_method' => 'card',
            'payment_provider' => 'paystack',
            'shipping_method' => 'standard',
        ])->assertCreated()->json('order');

        $this->postJson('/api/payments/orders/' . $order['id'] . '/initialize')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Unable to initialize Paystack transaction.');
    }

    public function test_paystack_live_mode_uses_live_secret_key(): void
    {
        config([
            'services.paystack.secret_key' => null,
            'services.paystack.webhook_secret' => null,
        ]);

        $customer = $this->makeUserWithRole('Customer', 'customer-live-mode@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-live-mode@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        $paystack = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $paystack->mode = 'live';
        $paystack->is_enabled = true;
        $paystack->is_visible = true;
        $paystack->extra_config = [
            'credentials' => [
                'sandbox' => [
                    'secret_key' => 'sk_test_sandbox_should_not_be_used',
                ],
                'live' => [
                    'secret_key' => 'sk_live_db_for_initialize',
                ],
            ],
        ];
        $paystack->save();

        Sanctum::actingAs($customer);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.test/pay-live',
                    'access_code' => 'ACCESS_CODE_LIVE',
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

        $this->postJson('/api/payments/orders/' . $order['id'] . '/initialize')->assertOk();

        Http::assertSent(function (HttpRequest $request) {
            return str_contains($request->url(), '/transaction/initialize')
                && $request->hasHeader('Authorization', 'Bearer sk_live_db_for_initialize');
        });
    }

    public function test_verify_uses_secret_key_for_selected_mode_and_marks_payment_paid(): void
    {
        config([
            'services.paystack.secret_key' => null,
            'services.paystack.webhook_secret' => null,
        ]);

        $customer = $this->makeUserWithRole('Customer', 'customer-verify-mode@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-verify-mode@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        $paystack = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $paystack->mode = 'live';
        $paystack->is_enabled = true;
        $paystack->is_visible = true;
        $paystack->extra_config = [
            'credentials' => [
                'sandbox' => [
                    'public_key' => 'pk_test_verify_mode_sandbox',
                    'secret_key' => 'sk_test_verify_mode_sandbox',
                ],
                'live' => [
                    'public_key' => 'pk_live_verify_mode_live',
                    'secret_key' => 'sk_live_verify_mode_live',
                ],
            ],
        ];
        $paystack->save();

        Sanctum::actingAs($customer);

        Http::fake([
            '*/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.test/pay-verify-mode',
                    'access_code' => 'ACCESS_CODE_VERIFY_MODE',
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

        $initialized = $this->postJson('/api/payments/orders/' . $order['id'] . '/initialize')->assertOk()->json();
        $paymentId = data_get($initialized, 'payment.id');
        $reference = data_get($initialized, 'checkout.reference');

        $this->postJson('/api/payments/' . $paymentId . '/verify', [
            'provider' => 'paystack',
            'reference' => $reference,
        ])->assertOk()->assertJsonPath('result.status', 'paid');

        Http::assertSent(function (HttpRequest $request) {
            return str_contains($request->url(), '/transaction/verify/')
                && $request->hasHeader('Authorization', 'Bearer sk_live_verify_mode_live');
        });
    }
}
