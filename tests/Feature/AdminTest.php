<?php

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_admin_dashboard_requires_admin_role(): void
    {
        $customer = $this->makeUserWithRole('Customer', 'customer-admin@test.com');
        Sanctum::actingAs($customer);
        $this->getJson('/api/admin/dashboard')->assertForbidden();

        $admin = $this->makeUserWithRole('Admin', 'admin@test.com');
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/dashboard')->assertOk();
    }
}
