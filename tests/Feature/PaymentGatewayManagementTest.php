<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class PaymentGatewayManagementTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_admin_can_list_payment_gateways(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-gateway-list@test.com');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/payment-gateways')
            ->assertOk()
            ->assertJsonStructure([
                'gateways' => [
                    ['provider', 'display_name', 'is_enabled', 'is_visible', 'is_default', 'readiness'],
                ],
            ]);
    }

    public function test_non_admin_cannot_manage_payment_gateways(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-gateway-forbidden@test.com');
        Sanctum::actingAs($customer);

        $this->getJson('/api/admin/payment-gateways')->assertForbidden();
    }

    public function test_admin_cannot_enable_unconfigured_gateway(): void
    {
        config([
            'services.flutterwave.secret_key' => null,
            'services.flutterwave.webhook_secret' => null,
        ]);

        $admin = $this->makeUserWithRole('Admin', 'admin-gateway-unconfigured@test.com');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/payment-gateways')->assertOk();

        $this->putJson('/api/admin/payment-gateways/flutterwave', [
            'is_enabled' => true,
        ])->assertStatus(422);
    }

    public function test_admin_can_set_configured_gateway_as_default(): void
    {
        config([
            'services.flutterwave.secret_key' => 'test_flutterwave_secret',
            'services.flutterwave.webhook_secret' => 'test_flutterwave_webhook',
        ]);

        $admin = $this->makeUserWithRole('Admin', 'admin-gateway-default@test.com');
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/payment-gateways')->assertOk();

        $this->putJson('/api/admin/payment-gateways/flutterwave', [
            'is_enabled' => true,
            'is_visible' => true,
        ])->assertOk();

        $this->postJson('/api/admin/payment-gateways/flutterwave/set-default')
            ->assertOk()
            ->assertJsonPath('gateway.provider', 'flutterwave')
            ->assertJsonPath('gateway.is_default', true);

        $this->assertSame(1, PaymentGateway::query()->where('is_default', true)->count());
    }

    public function test_public_gateways_endpoint_returns_enabled_visible_and_configured_only(): void
    {
        config([
            'services.flutterwave.secret_key' => null,
            'services.flutterwave.webhook_secret' => null,
        ]);

        $this->getJson('/api/payments/gateways')->assertOk();

        PaymentGateway::query()->where('provider', 'paystack')->update([
            'is_enabled' => true,
            'is_visible' => true,
        ]);

        PaymentGateway::query()->where('provider', 'flutterwave')->update([
            'is_enabled' => true,
            'is_visible' => true,
        ]);

        $response = $this->getJson('/api/payments/gateways')->assertOk()->json('gateways');

        $providers = collect($response)->pluck('provider')->all();
        $this->assertContains('paystack', $providers);
        $this->assertNotContains('flutterwave', $providers);
    }

    public function test_admin_can_enable_paystack_with_database_credentials_when_env_is_empty(): void
    {
        config([
            'services.paystack.secret_key' => null,
            'services.paystack.webhook_secret' => null,
        ]);

        $admin = $this->makeUserWithRole('Admin', 'admin-paystack-db-credentials@test.com');
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/payment-gateways/paystack', [
            'mode' => 'sandbox',
            'is_enabled' => true,
            'is_visible' => true,
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'public_key' => 'pk_test_db_123',
                        'secret_key' => 'sk_test_db_123',
                        'webhook_secret' => 'whsec_db_123',
                    ],
                ],
            ],
        ])->assertOk()->assertJsonPath('gateway.readiness.is_configured', true);

        $response = $this->getJson('/api/payments/gateways')->assertOk()->json('gateways');

        $paystack = collect($response)->firstWhere('provider', 'paystack');
        $this->assertNotNull($paystack);
        $this->assertSame('pk_test_db_123', data_get($paystack, 'extra_config.public_key'));
    }

    public function test_blank_sandbox_secret_and_webhook_preserve_existing_values(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-gateway-preserve-sandbox@test.com');
        Sanctum::actingAs($admin);

        $gateway = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $gateway->update([
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'public_key' => 'pk_test_old_sandbox',
                        'secret_key' => Crypt::encryptString('sk_test_old_sandbox'),
                        'webhook_secret' => Crypt::encryptString('whsec_old_sandbox'),
                    ],
                    'live' => [
                        'public_key' => 'pk_live_existing',
                        'secret_key' => Crypt::encryptString('sk_live_existing'),
                        'webhook_secret' => Crypt::encryptString('whsec_live_existing'),
                    ],
                ],
            ],
        ]);

        $this->putJson('/api/admin/payment-gateways/paystack', [
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'public_key' => 'pk_test_updated_only',
                        'secret_key' => '',
                        'webhook_secret' => '',
                    ],
                ],
            ],
        ])->assertOk();

        $updated = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $sandbox = data_get($updated->extra_config, 'credentials.sandbox', []);
        $live = data_get($updated->extra_config, 'credentials.live', []);

        $this->assertSame('pk_test_updated_only', $sandbox['public_key']);
        $this->assertSame('sk_test_old_sandbox', Crypt::decryptString($sandbox['secret_key']));
        $this->assertSame('whsec_old_sandbox', Crypt::decryptString($sandbox['webhook_secret']));
        $this->assertSame('sk_live_existing', Crypt::decryptString($live['secret_key']));
        $this->assertSame('whsec_live_existing', Crypt::decryptString($live['webhook_secret']));
    }

    public function test_blank_live_secret_preserves_existing_live_secret(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-gateway-preserve-live@test.com');
        Sanctum::actingAs($admin);

        $gateway = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $gateway->update([
            'extra_config' => [
                'credentials' => [
                    'live' => [
                        'public_key' => 'pk_live_existing',
                        'secret_key' => Crypt::encryptString('sk_live_existing'),
                        'webhook_secret' => Crypt::encryptString('whsec_live_existing'),
                    ],
                ],
            ],
        ]);

        $this->putJson('/api/admin/payment-gateways/paystack', [
            'mode' => 'live',
            'extra_config' => [
                'credentials' => [
                    'live' => [
                        'secret_key' => null,
                        'webhook_secret' => '',
                        'callback_url' => 'https://example.com/callback',
                    ],
                ],
            ],
        ])->assertOk();

        $updated = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $live = data_get($updated->extra_config, 'credentials.live', []);

        $this->assertSame('sk_live_existing', Crypt::decryptString($live['secret_key']));
        $this->assertSame('whsec_live_existing', Crypt::decryptString($live['webhook_secret']));
        $this->assertSame('https://example.com/callback', $live['callback_url']);
    }

    public function test_non_empty_secret_replaces_existing_secret(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-gateway-replace-secret@test.com');
        Sanctum::actingAs($admin);

        $gateway = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $gateway->update([
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'secret_key' => Crypt::encryptString('sk_test_before_replace'),
                    ],
                ],
            ],
        ]);

        $this->putJson('/api/admin/payment-gateways/paystack', [
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'secret_key' => 'sk_test_after_replace',
                    ],
                ],
            ],
        ])->assertOk();

        $updated = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $sandbox = data_get($updated->extra_config, 'credentials.sandbox', []);

        $this->assertSame('sk_test_after_replace', Crypt::decryptString($sandbox['secret_key']));
    }

    public function test_blank_live_webhook_secret_preserves_existing_value(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-gateway-preserve-live-webhook@test.com');
        Sanctum::actingAs($admin);

        $gateway = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $gateway->update([
            'extra_config' => [
                'credentials' => [
                    'live' => [
                        'secret_key' => Crypt::encryptString('sk_live_keep_this'),
                        'webhook_secret' => Crypt::encryptString('whsec_live_keep_this'),
                    ],
                ],
            ],
        ]);

        $this->putJson('/api/admin/payment-gateways/paystack', [
            'mode' => 'live',
            'extra_config' => [
                'credentials' => [
                    'live' => [
                        'webhook_secret' => '',
                    ],
                ],
            ],
        ])->assertOk();

        $updated = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $live = data_get($updated->extra_config, 'credentials.live', []);

        $this->assertSame('whsec_live_keep_this', Crypt::decryptString($live['webhook_secret']));
        $this->assertSame('sk_live_keep_this', Crypt::decryptString($live['secret_key']));
    }

    public function test_updating_public_key_does_not_erase_secret_key(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-gateway-public-key-update@test.com');
        Sanctum::actingAs($admin);

        $gateway = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $gateway->update([
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'public_key' => 'pk_test_before',
                        'secret_key' => Crypt::encryptString('sk_test_keep_when_public_updates'),
                    ],
                ],
            ],
        ]);

        $this->putJson('/api/admin/payment-gateways/paystack', [
            'extra_config' => [
                'credentials' => [
                    'sandbox' => [
                        'public_key' => 'pk_test_after',
                    ],
                ],
            ],
        ])->assertOk();

        $updated = PaymentGateway::query()->where('provider', 'paystack')->firstOrFail();
        $sandbox = data_get($updated->extra_config, 'credentials.sandbox', []);

        $this->assertSame('pk_test_after', $sandbox['public_key']);
        $this->assertSame('sk_test_keep_when_public_updates', Crypt::decryptString($sandbox['secret_key']));
    }
}
