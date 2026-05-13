<?php

namespace Tests\Feature;

use App\Models\BasketComponent;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReferralReward;
use App\Models\Sku;
use App\Models\Stock;
use App\Services\InventoryService;
use App\Services\ReferralService;
use App\Services\RewardWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class BasketAndReferralTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_basket_stock_calculation_and_cart_addition_use_component_stock(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'basket-customer@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'basket-vendor@test.com');
        $basket = $this->makeBasketProduct($vendor);

        Sanctum::actingAs($customer);

        $this->getJson('/api/products/' . $basket['basket']->slug)
            ->assertOk()
            ->assertJsonPath('basket_available_stock', 5);

        $this->postJson('/api/cart/items', [
            'product_id' => $basket['basket']->id,
            'quantity' => 5,
        ])->assertCreated();

        $this->postJson('/api/cart/items', [
            'product_id' => $basket['basket']->id,
            'quantity' => 1,
        ])->assertStatus(422);
    }

    public function test_basket_checkout_deducts_component_skus_only_and_commit_is_idempotent(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'basket-checkout@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'basket-checkout-vendor@test.com');
        $admin = $this->makeUserWithRole('Admin', 'basket-checkout-admin@test.com');
        $basket = $this->makeBasketProduct($vendor);
        $address = $this->makeAddress($customer);

        Sanctum::actingAs($customer);
        $this->postJson('/api/cart/items', [
            'product_id' => $basket['basket']->id,
            'quantity' => 2,
        ])->assertCreated();

        $orderPayload = $this->postJson('/api/checkout/place-order', [
            'address_id' => $address->id,
            'payment_provider' => 'paystack',
        ])->assertCreated()->json('order');

        $order = Order::with('items.components')->findOrFail($orderPayload['id']);
        $this->assertCount(1, $order->items);
        $this->assertCount(2, $order->items[0]->components);

        $parentStockBefore = (int) Stock::where('sku_id', $basket['parentSku']->id)->value('on_hand');

        app(InventoryService::class)->commitOrder($order->fresh(['items.sku', 'items.components.componentSku']), $admin);
        app(InventoryService::class)->commitOrder($order->fresh(['items.sku', 'items.components.componentSku']), $admin);

        $this->assertEquals($parentStockBefore, (int) Stock::where('sku_id', $basket['parentSku']->id)->value('on_hand'));
        $this->assertDatabaseHas('order_item_components', [
            'order_item_id' => $order->items[0]->id,
            'component_sku_id' => $basket['componentA']['sku']->id,
            'inventory_committed' => 1,
        ]);

        $this->assertEquals(2, DB::table('inventory_ledger_entries')->where('reference_type', 'order_item_component')->count());
    }

    public function test_referral_registration_capture_and_reward_generated_once(): void
    {
        Mail::fake();

        $referrer = $this->makeUserWithRole('Customer', 'referrer@test.com');
        $code = app(ReferralService::class)->activeCodeFor($referrer);
        $this->enableReferralSettings();

        $this->postJson('/api/auth/register', [
            'name' => 'Referred Customer',
            'email' => 'referred@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'referral_code' => $code->code,
        ])->assertAccepted();

        $pending = DB::table('pending_customer_registrations')->where('email', 'referred@test.com')->first();
        $this->assertEquals($code->code, $pending->referral_code);

        DB::table('pending_customer_registrations')->where('email', 'referred@test.com')->update([
            'verification_code_hash' => Hash::make('123456'),
        ]);

        $verified = $this->postJson('/api/auth/register/verify', [
            'email' => 'referred@test.com',
            'code' => '123456',
        ])->assertCreated()->json('user');

        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $verified['id'],
        ]);

        $order = Order::create([
            'user_id' => $verified['id'],
            'status' => 'processing',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'shipping_cost' => 0,
            'tax' => 0,
            'total' => 1000,
            'placed_at' => now(),
        ]);

        app(ReferralService::class)->handleOrderEvent($order, 'paid_order');
        app(ReferralService::class)->handleOrderEvent($order, 'paid_order');

        $this->assertEquals(1, ReferralReward::query()->where('order_id', $order->id)->count());
    }

    public function test_reward_wallet_credit_debit_and_reversal_are_ledger_based(): void
    {
        $user = $this->makeUserWithRole('Customer', 'wallet@test.com');
        $wallet = app(RewardWalletService::class);

        $wallet->credit($user, 500);
        $wallet->debit($user, 125);
        $wallet->reverse($user, 75);

        $this->assertDatabaseHas('reward_wallets', [
            'user_id' => $user->id,
            'balance' => 300,
        ]);

        $this->assertEquals(3, DB::table('reward_wallet_transactions')->count());
    }

    protected function makeBasketProduct($vendor): array
    {
        $componentA = $this->makeProductWithStock($vendor);
        $componentB = $this->makeProductWithStock($vendor);
        $componentA['sku']->stocks()->update(['on_hand' => 10, 'reserved' => 0]);
        $componentB['sku']->stocks()->update(['on_hand' => 5, 'reserved' => 0]);

        $basket = Product::create([
            'vendor_id' => $vendor->id,
            'title' => 'Pantry Basket',
            'name' => 'Pantry Basket',
            'slug' => 'pantry-basket-' . uniqid(),
            'description' => 'Basket product',
            'base_price' => 1500,
            'price' => 1500,
            'status' => 'active',
            'product_type' => Product::TYPE_BASKET,
            'has_options' => false,
        ]);
        $basket->categories()->attach($componentA['category']->id);

        $parentSku = Sku::create([
            'product_id' => $basket->id,
            'sku_code' => 'BASKET-' . strtoupper(uniqid()),
            'price' => 1500,
            'stock_quantity' => 0,
            'active' => true,
        ]);

        BasketComponent::create([
            'basket_product_id' => $basket->id,
            'component_sku_id' => $componentA['sku']->id,
            'quantity' => 2,
            'sort_order' => 0,
            'is_required' => true,
        ]);

        BasketComponent::create([
            'basket_product_id' => $basket->id,
            'component_sku_id' => $componentB['sku']->id,
            'quantity' => 1,
            'sort_order' => 1,
            'is_required' => true,
        ]);

        return compact('basket', 'parentSku', 'componentA', 'componentB');
    }

    protected function enableReferralSettings(): void
    {
        foreach ([
            'referral_enabled' => '1',
            'referral_reward_basis' => 'fixed',
            'referral_reward_value' => '100',
            'referral_first_order_only' => '1',
            'referral_minimum_order_amount' => '0',
            'referral_trigger' => 'paid_order',
            'referral_approval_mode' => 'manual',
            'referral_wallet_redemption_enabled' => '0',
        ] as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
