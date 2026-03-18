<?php

namespace App\Services;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;

class ShippingRateService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function quote(array $destination, array $items): array
    {
        [$totalWeight, $totalValue] = $this->buildCartMetrics($items);

        $zone = $this->findZone($destination);
        $methods = ShippingMethod::query()->where('active', true)->orderBy('id')->get();

        if ($methods->isEmpty()) {
            return $this->getDefaultRates($zone);
        }

        $quotes = [];

        foreach ($methods as $method) {
            $rule = $zone
                ? $zone->rules()
                    ->where('active', true)
                    ->where(function ($query) use ($method) {
                        $query->where('shipping_method_id', $method->id)
                            ->orWhereJsonContains('config->method', $method->code)
                            ->orWhereJsonContains('config->method', $method->name);
                    })
                    ->orderBy('priority')
                    ->first()
                : null;

            $amount = $this->calculateAmount($method, $rule?->rule_type, $rule?->config ?? [], $zone, $totalWeight, $totalValue);

            $quotes[] = [
                'method' => $method->code,
                'method_id' => $method->id,
                'name' => $method->name,
                'amount' => (int) round(max(0, $amount)),
                'zone_id' => $zone?->id,
                'zone_name' => $zone?->name,
                'cod_available' => (bool) ($rule?->config['cod_available'] ?? $method->supports_cod),
            ];
        }

        return $quotes;
    }

    protected function findZone(array $destination): ?ShippingZone
    {
        $state = strtolower((string) ($destination['state'] ?? ''));
        $city = strtolower((string) ($destination['city'] ?? ''));
        $area = strtolower((string) ($destination['area_or_district'] ?? ''));
        $country = strtolower((string) ($destination['country'] ?? ''));

        $zones = ShippingZone::query()
            ->where('active', true)
            ->with('rules')
            ->orderBy('is_fallback')
            ->orderBy('id')
            ->get();

        foreach ($zones as $zone) {
            if ($this->matchesCoverage($state, $city, $area, $zone->coverage_states, $zone->coverage_cities, $zone->coverage_areas)) {
                return $zone;
            }

            $region = strtolower((string) $zone->region);
            if ($region !== '' && in_array($region, [$state, $city, $country], true)) {
                return $zone;
            }
        }

        return $zones->firstWhere('is_fallback', true);
    }

    protected function matchesCoverage(
        string $state,
        string $city,
        string $area,
        ?array $states,
        ?array $cities,
        ?array $areas
    ): bool {
        $normalizedStates = collect($states ?? [])->map(fn ($value) => strtolower((string) $value));
        $normalizedCities = collect($cities ?? [])->map(fn ($value) => strtolower((string) $value));
        $normalizedAreas = collect($areas ?? [])->map(fn ($value) => strtolower((string) $value));

        $stateMatch = $normalizedStates->isEmpty() || ($state !== '' && $normalizedStates->contains($state));
        $cityMatch = $normalizedCities->isEmpty() || ($city !== '' && $normalizedCities->contains($city));
        $areaMatch = $normalizedAreas->isEmpty() || ($area !== '' && $normalizedAreas->contains($area));

        return $stateMatch && $cityMatch && $areaMatch;
    }

    protected function calculateAmount(
        ShippingMethod $method,
        ?string $ruleType,
        array $config,
        ?ShippingZone $zone,
        float $weight,
        float $value
    ): float {
        $baseFee = (float) ($config['rate'] ?? $zone?->default_fee ?? $method->base_fee);
        $amount = $baseFee;

        switch ($ruleType) {
            case 'weight_based':
                $baseRate = (float) ($config['base_rate'] ?? $baseFee);
                $perKg = (float) ($config['per_kg'] ?? 0);
                $amount = $baseRate + ($weight * $perKg);
                break;

            case 'price_based':
                $percentage = (float) ($config['percentage'] ?? 0);
                $minimum = (float) ($config['min'] ?? $baseFee);
                $amount = max($minimum, $value * ($percentage / 100));
                break;

            case 'free':
                $threshold = (float) ($config['min_order'] ?? $method->free_shipping_threshold ?? 0);
                $fallback = (float) ($config['fallback_rate'] ?? $baseFee);
                $amount = $threshold > 0 && $value >= $threshold ? 0 : $fallback;
                break;

            case 'flat':
            default:
                $amount = $baseFee;
                break;
        }

        $amount += $weight * (float) $method->per_kg_surcharge;

        if (str_contains(strtolower((string) $method->code), 'express')) {
            $amount += (float) $method->express_surcharge;
        }

        if ($method->free_shipping_threshold && $value >= (float) $method->free_shipping_threshold) {
            return 0;
        }

        return $amount;
    }

    protected function buildCartMetrics(array $items): array
    {
        $totalWeight = 0;
        $totalValue = 0;

        foreach ($items as $item) {
            $sku = $item['sku'];
            $quantity = (int) ($item['quantity'] ?? 1);

            $totalWeight += ((float) ($sku->weight ?? 0)) * $quantity;
            $totalValue += ((float) ($sku->price ?? 0)) * $quantity;
        }

        return [$totalWeight, $totalValue];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getDefaultRates(?ShippingZone $zone = null): array
    {
        $baseRate = (float) ($zone?->default_fee ?? 1500);

        return [
            [
                'method' => 'standard',
                'method_id' => null,
                'name' => 'Standard Shipping (3-5 business days)',
                'amount' => (int) round($baseRate),
                'zone_id' => $zone?->id,
                'zone_name' => $zone?->name,
                'cod_available' => true,
            ],
            [
                'method' => 'express',
                'method_id' => null,
                'name' => 'Express Shipping (1-2 business days)',
                'amount' => (int) round($baseRate + 2000),
                'zone_id' => $zone?->id,
                'zone_name' => $zone?->name,
                'cod_available' => false,
            ],
            [
                'method' => 'pickup',
                'method_id' => null,
                'name' => 'Pickup Station',
                'amount' => 0,
                'zone_id' => $zone?->id,
                'zone_name' => $zone?->name,
                'cod_available' => false,
            ],
        ];
    }
}
