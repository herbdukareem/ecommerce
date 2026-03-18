<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_admin_can_manage_core_modules(): void
    {
        $admin = $this->makeUserWithRole('Admin', 'admin-management@test.com');
        Sanctum::actingAs($admin);

        $category = $this->postJson('/api/admin/categories', [
            'name' => 'Admin Category',
        ])->assertCreated()->json('category');

        $this->postJson('/api/admin/vendors', [
            'name' => 'Vendor One',
            'email' => 'vendor-admin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_verified' => true,
        ])->assertCreated();

        $this->postJson('/api/admin/shipping/zones', [
            'name' => 'Lagos Zone',
            'region' => 'Lagos',
        ])->assertCreated();

        $this->getJson('/api/admin/orders')->assertOk();

        $this->postJson('/api/admin/products', [
            'name' => 'Admin Product',
            'price' => 120,
            'status' => 'active',
            'category_id' => $category['id'],
        ])->assertCreated();
    }
}
