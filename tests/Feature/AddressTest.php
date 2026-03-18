<?php

namespace Tests\Feature;

use App\Models\LocationCountry;
use App\Models\LocationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\CreatesCommerceData;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCommerceData;

    public function test_customer_can_manage_addresses(): void
    {
        $country = LocationCountry::query()->create([
            'code' => 'NG',
            'name' => 'Nigeria',
            'active' => true,
            'sort_order' => 1,
        ]);

        LocationState::query()->create([
            'country_id' => $country->id,
            'code' => 'LA',
            'name' => 'Lagos',
            'active' => true,
            'sort_order' => 1,
        ]);

        $customer = $this->makeUserWithRole('Customer', 'customer-address@test.com');
        Sanctum::actingAs($customer);

        $created = $this->postJson('/api/addresses', [
            'full_name' => 'Jane Doe',
            'phone' => '08012345678',
            'country' => 'Nigeria',
            'country_code' => 'NG',
            'state' => 'Lagos',
            'state_code' => 'LA',
            'city' => 'Ikeja',
            'area_or_district' => 'Allen',
            'address_line_1' => '15 Allen Avenue',
            'address_line_2' => 'Block B',
            'landmark' => 'Near UBA Branch',
            'postal_code' => '100001',
            'is_default' => true,
        ])->assertCreated()->assertJsonPath('address.country_code', 'NG')->assertJsonPath('address.state_code', 'LA')->json('address');

        $this->getJson('/api/addresses')->assertOk()->assertJsonCount(1);

        $this->putJson('/api/addresses/' . $created['id'], [
            'city' => 'Yaba',
            'area_or_district' => 'Sabo',
            'landmark' => 'Near Unilag Gate',
            'is_default' => true,
        ])->assertOk();

        $this->patchJson('/api/addresses/' . $created['id'] . '/default')->assertOk();

        $this->deleteJson('/api/addresses/' . $created['id'])->assertOk();
    }

    public function test_user_cannot_edit_another_users_address(): void
    {
        $owner = $this->makeUserWithRole('Customer', 'owner-address@test.com');
        $other = $this->makeUserWithRole('Customer', 'other-address@test.com');
        $address = $this->makeAddress($owner);

        Sanctum::actingAs($other);

        $this->putJson('/api/addresses/' . $address->id, [
            'city' => 'Surulere',
        ])->assertNotFound();
    }
}
