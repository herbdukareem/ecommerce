<?php

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_guest_add_to_cart_is_rejected_when_auth_is_required(): void
    {
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-cart-guest@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])
            ->assertUnauthorized();
    }

    public function test_customer_can_add_update_and_remove_cart_item(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-cart@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-cart@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 2])
            ->assertCreated();

        $cart = $this->getJson('/api/cart')->assertOk()->json();
        $itemId = $cart['items'][0]['id'];

        $this->putJson('/api/cart/items/' . $itemId, ['quantity' => 3])->assertOk();
        $this->deleteJson('/api/cart/items/' . $itemId)->assertOk();
    }

    public function test_invalid_sku_is_rejected_for_cart_addition(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-cart-invalid-sku@test.com');
        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', ['sku_id' => 999999, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku_id']);
    }

    public function test_out_of_stock_item_is_rejected_for_cart_addition(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-cart-stock@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-cart-stock@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        $commerce['sku']->stocks()->update(['on_hand' => 0, 'reserved' => 0]);
        $commerce['sku']->update(['stock_quantity' => 0]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', ['sku_id' => $commerce['sku']->id, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Insufficient stock available');
    }

    public function test_simple_product_add_to_cart_accepts_quantity_greater_than_one(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-cart-qty-simple@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-cart-qty-simple@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'quantity' => 3,
        ])->assertCreated();

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('items.0.quantity', 3);
    }

    public function test_quantity_below_one_is_rejected(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-cart-qty-min@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-cart-qty-min@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'quantity' => 0,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_quantity_above_stock_is_rejected(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-cart-qty-over-stock@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-cart-qty-over-stock@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'quantity' => 999,
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Insufficient stock available');
    }

    public function test_existing_cart_line_merges_quantity_correctly(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-cart-qty-merge@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-cart-qty-merge@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'quantity' => 2,
        ])->assertCreated();

        $this->postJson('/api/cart/items', [
            'product_id' => $commerce['product']->id,
            'quantity' => 3,
        ])->assertCreated();

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.quantity', 5);
    }
}
