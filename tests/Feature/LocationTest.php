<?php

namespace Tests\Feature;

use App\Models\LocationCity;
use App\Models\LocationCountry;
use App\Models\LocationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_location_endpoints_return_countries_states_and_cities(): void
    {
        $country = LocationCountry::query()->create([
            'code' => 'NG',
            'name' => 'Nigeria',
            'active' => true,
            'sort_order' => 1,
        ]);

        $state = LocationState::query()->create([
            'country_id' => $country->id,
            'code' => 'LA',
            'name' => 'Lagos',
            'active' => true,
            'sort_order' => 1,
        ]);

        LocationCity::query()->create([
            'state_id' => $state->id,
            'code' => 'IKJ',
            'name' => 'Ikeja',
            'active' => true,
            'sort_order' => 1,
        ]);

        $this->getJson('/api/locations/countries')
            ->assertOk()
            ->assertJsonFragment(['code' => 'NG', 'name' => 'Nigeria']);

        $this->getJson('/api/locations/states?country=NG')
            ->assertOk()
            ->assertJsonFragment(['code' => 'LA', 'name' => 'Lagos']);

        $this->getJson('/api/locations/cities?country=NG&state=LA')
            ->assertOk()
            ->assertJsonFragment(['code' => 'IKJ', 'name' => 'Ikeja']);
    }

    public function test_autocomplete_returns_not_configured_without_provider_keys(): void
    {
        config(['services.geo.autocomplete_provider' => 'none']);

        $this->getJson('/api/locations/autocomplete?type=landmark&query=ikeja')
            ->assertOk()
            ->assertJsonFragment(['configured' => false]);
    }
}
