<?php

namespace Tests\Feature;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_quote_calculated_by_zone(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'zone-shipping@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'zone-vendor@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        $method = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'code' => 'standard',
            'base_fee' => 1000,
            'active' => true,
        ]);

        $zone = ShippingZone::create([
            'name' => 'Lagos Metro',
            'region' => 'Lagos',
            'coverage_states' => ['lagos'],
            'default_fee' => 1200,
            'active' => true,
        ]);

        ShippingZoneRule::create([
            'shipping_zone_id' => $zone->id,
            'shipping_method_id' => $method->id,
            'rule_type' => 'flat',
            'config' => ['rate' => 1800, 'method' => 'standard'],
            'priority' => 1,
            'active' => true,
        ]);

        Sanctum::actingAs($customer);
        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $this->postJson('/api/checkout/quote-shipping', [
            'address_id' => $address->id,
        ])->assertOk()->assertJsonPath('quotes.0.amount', 1800);
    }

    public function test_fallback_quote_used_when_no_zone_matches(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'fallback-shipping@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'fallback-vendor@test.com');
        $commerce = $this->makeProductWithStock($vendor);
        $address = $this->makeAddress($customer);

        $method = ShippingMethod::create([
            'name' => 'Standard Delivery',
            'code' => 'standard',
            'base_fee' => 1000,
            'active' => true,
        ]);

        ShippingZone::create([
            'name' => 'Fallback Zone',
            'region' => 'Nigeria',
            'default_fee' => 4500,
            'is_fallback' => true,
            'active' => true,
        ]);

        Sanctum::actingAs($customer);
        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])->assertCreated();

        $quote = $this->postJson('/api/checkout/quote-shipping', [
            'address_id' => $address->id,
        ])->assertOk()->json('quotes');

        $this->assertSame('standard', $quote[0]['method']);
        $this->assertGreaterThan(0, $quote[0]['amount']);
        $this->assertNotNull($method->id);
    }
}
