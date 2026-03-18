<?php

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_customer_can_create_review_and_list_reviews(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-review@test.com');
        $vendor = $this->makeUserWithRole('Vendor', 'vendor-review@test.com');
        $commerce = $this->makeProductWithStock($vendor);

        Sanctum::actingAs($customer);

        $this->postJson('/api/products/' . $commerce['product']->id . '/reviews', [
            'rating' => 5,
            'title' => 'Great product',
            'comment' => 'Works as expected',
        ])->assertCreated();

        $this->getJson('/api/products/' . $commerce['product']->id . '/reviews')->assertOk();
    }
}
