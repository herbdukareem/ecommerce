<?php

namespace Database\Seeders;

use App\Models\DispatchTimeSlot;
use App\Models\OperationArea;
use App\Models\OperationCity;
use Illuminate\Database\Seeder;

class DispatchAndOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $lagos = OperationCity::updateOrCreate(
            ['code' => 'Minna'],
            ['name' => 'Minna', 'status' => 'active', 'sort_order' => 1]
        );

        $abuja = OperationCity::updateOrCreate(
            ['code' => 'Suleja'],
            ['name' => 'Suleja', 'status' => 'active', 'sort_order' => 2]
        );

        $areas = [
            ['city_id' => $lagos->id, 'name' => 'Bosso', 'delivery_fee' => 2000, 'sort_order' => 1],
            ['city_id' => $lagos->id, 'name' => 'GK', 'delivery_fee' => 2500, 'sort_order' => 2],
            ['city_id' => $lagos->id, 'name' => 'kpakwungu', 'delivery_fee' => 2000, 'sort_order' => 3],
            ['city_id' => $lagos->id, 'name' => 'Mobil', 'delivery_fee' => 2200, 'sort_order' => 4],
            ['city_id' => $lagos->id, 'name' => 'Maitunbi', 'delivery_fee' => 2300, 'sort_order' => 5],
        ];

        foreach ($areas as $area) {
            OperationArea::updateOrCreate(
                ['city_id' => $area['city_id'], 'name' => $area['name']],
                ['delivery_fee' => $area['delivery_fee'], 'status' => 'active', 'sort_order' => $area['sort_order']]
            );
        }

        $slots = [
            ['label' => 'Morning delivery', 'start_time' => '09:00', 'end_time' => '11:00', 'sort_order' => 1],
            ['label' => 'Afternoon delivery', 'start_time' => '12:00', 'end_time' => '15:00', 'sort_order' => 2],
            ['label' => 'Evening delivery', 'start_time' => '16:00', 'end_time' => '19:00', 'sort_order' => 3],
        ];

        foreach ($slots as $slot) {
            DispatchTimeSlot::updateOrCreate(
                ['label' => $slot['label']],
                [
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'status' => 'active',
                    'sort_order' => $slot['sort_order'],
                ]
            );
        }
    }
}
