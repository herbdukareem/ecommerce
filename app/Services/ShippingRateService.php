<?php

namespace App\Services;

use App\Models\ShippingZone;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Cache;

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
        // Calculate total weight and dimensions
        $totalWeight = 0;
        $totalValue = 0;

        foreach ($items as $item) {
            $sku = $item['sku'];
            $quantity = $item['quantity'];

            $totalWeight += ($sku->weight ?? 0) * $quantity;
            $totalValue += ($sku->price ?? 0) * $quantity;
        }

        // Find matching shipping zone
        $zone = $this->findZone($destination);

        if (!$zone) {
            // Return default rates if no zone found
            return $this->getDefaultRates($totalWeight, $totalValue);
        }

        // Apply zone rules
        return $this->applyZoneRules($zone, $totalWeight, $totalValue);
    }

    /**
     * Find shipping zone matching the destination.
     */
    protected function findZone(array $destination): ?ShippingZone
    {
        // Simple zone matching by country/state
        // In production, this would be more sophisticated
        $zone = ShippingZone::where('region', $destination['country'])
            ->orWhere('region', $destination['state'] ?? '')
            ->first();

        return $zone;
    }

    /**
     * Apply zone-specific rules to calculate shipping cost.
     */
    protected function applyZoneRules(ShippingZone $zone, float $weight, float $value): array
    {
        $quotes = [];

        foreach ($zone->rules as $rule) {
            $config = $rule->config;
            $amount = 0;

            switch ($rule->rule_type) {
                case 'flat':
                    $amount = $config['rate'] ?? 1000;
                    break;

                case 'weight_based':
                    $baseRate = $config['base_rate'] ?? 500;
                    $perKg = $config['per_kg'] ?? 100;
                    $amount = $baseRate + ($weight * $perKg);
                    break;

                case 'price_based':
                    $percentage = $config['percentage'] ?? 5;
                    $minAmount = $config['min'] ?? 500;
                    $amount = max($minAmount, $value * ($percentage / 100));
                    break;

                case 'free':
                    $minOrder = $config['min_order'] ?? 10000;
                    $amount = $value >= $minOrder ? 0 : ($config['fallback_rate'] ?? 1000);
                    break;
            }

            $quotes[] = [
                'method' => $config['method'] ?? 'standard',
                'amount' => (int) $amount,
                'name' => $config['name'] ?? 'Standard Shipping',
            ];
        }

        return $quotes ?: $this->getDefaultRates($weight, $value);
    }

    /**
     * Get default shipping rates when no zone is configured.
     */
    protected function getDefaultRates(float $weight, float $value): array
    {
        $baseRate = 1000; // ₦10.00

        return [
            [
                'method' => 'standard',
                'name' => 'Standard Shipping (5-7 days)',
                'amount' => $baseRate,
            ],
            [
                'method' => 'express',
                'name' => 'Express Shipping (2-3 days)',
                'amount' => $baseRate * 2,
            ],
            [
                'method' => 'overnight',
                'name' => 'Overnight Shipping',
                'amount' => $baseRate * 3,
            ],
        ];
    }
}