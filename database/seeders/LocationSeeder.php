<?php

namespace Database\Seeders;

use App\Models\LocationCity;
use App\Models\LocationCountry;
use App\Models\LocationState;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'code' => 'NG',
                'name' => 'Nigeria',
                'sort_order' => 1,
                'states' => [
                    ['code' => 'LA', 'name' => 'Lagos', 'cities' => ['Ikeja', 'Lekki', 'Yaba', 'Surulere', 'Victoria Island']],
                    ['code' => 'FC', 'name' => 'FCT Abuja', 'cities' => ['Abuja', 'Gwagwalada', 'Kuje']],
                    ['code' => 'KN', 'name' => 'Kano', 'cities' => ['Kano', 'Wudil']],
                    ['code' => 'RV', 'name' => 'Rivers', 'cities' => ['Port Harcourt', 'Obio-Akpor']],
                    ['code' => 'OY', 'name' => 'Oyo', 'cities' => ['Ibadan', 'Ogbomosho']],
                ],
            ],
            [
                'code' => 'GH',
                'name' => 'Ghana',
                'sort_order' => 2,
                'states' => [
                    ['code' => 'AA', 'name' => 'Greater Accra', 'cities' => ['Accra', 'Tema']],
                    ['code' => 'AS', 'name' => 'Ashanti', 'cities' => ['Kumasi']],
                ],
            ],
            [
                'code' => 'KE',
                'name' => 'Kenya',
                'sort_order' => 3,
                'states' => [
                    ['code' => 'NB', 'name' => 'Nairobi County', 'cities' => ['Nairobi']],
                    ['code' => 'MS', 'name' => 'Mombasa County', 'cities' => ['Mombasa']],
                ],
            ],
            [
                'code' => 'US',
                'name' => 'United States',
                'sort_order' => 4,
                'states' => [
                    ['code' => 'CA', 'name' => 'California', 'cities' => ['Los Angeles', 'San Francisco', 'San Diego']],
                    ['code' => 'NY', 'name' => 'New York', 'cities' => ['New York', 'Buffalo']],
                ],
            ],
        ];

        foreach ($countries as $countryIndex => $countryData) {
            $country = LocationCountry::query()->updateOrCreate(
                ['code' => strtoupper($countryData['code'])],
                [
                    'name' => $countryData['name'],
                    'active' => true,
                    'sort_order' => $countryData['sort_order'] ?? $countryIndex,
                ]
            );

            foreach ($countryData['states'] as $stateIndex => $stateData) {
                $state = LocationState::query()->updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'name' => $stateData['name'],
                    ],
                    [
                        'code' => $stateData['code'] ?? null,
                        'active' => true,
                        'sort_order' => $stateIndex,
                    ]
                );

                foreach ($stateData['cities'] as $cityIndex => $cityName) {
                    LocationCity::query()->updateOrCreate(
                        [
                            'state_id' => $state->id,
                            'name' => $cityName,
                        ],
                        [
                            'active' => true,
                            'sort_order' => $cityIndex,
                        ]
                    );
                }
            }
        }
    }
}
