<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
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
}
