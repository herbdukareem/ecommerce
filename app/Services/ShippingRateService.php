<?php

namespace App\Services;

use App\Models\ShippingZone;
use App\Models\ShippingMethod;

/**
 * Compute shipping rates using configured zones and rules.
 */
class ShippingRateService
{
    /**
     * Calculate quotes based on destination and cart metrics.
     *
     * @param array $destination
     * @param array $items
     * @return array<int, array{method:string, amount:int}>
     */
    public function quote(array $destination, array $items): array
    {
        // TODO: find matching shipping zone by region/zip and apply rules.
        // This stub returns fixed values for demonstration.
        return [
            ['method' => 'standard', 'amount' => 1000],
            ['method' => 'express', 'amount' => 2000],
        ];
    }
}