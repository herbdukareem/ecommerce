<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayOnDeliveryService
{
    public const METHOD = 'pay_on_delivery';

    public function settings(): array
    {
        return Cache::remember('pay_on_delivery_settings', 300, function () {
            $settings = DB::table('settings')
                ->whereIn('key', array_keys($this->defaults()))
                ->pluck('value', 'key')
                ->toArray();

            return [
                'pay_on_delivery_enabled' => filter_var($settings['pay_on_delivery_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'pay_on_delivery_min_order_amount' => (float) ($settings['pay_on_delivery_min_order_amount'] ?? 0),
                'pay_on_delivery_max_order_amount' => (float) ($settings['pay_on_delivery_max_order_amount'] ?? 0),
                'pay_on_delivery_allowed_city_ids' => $this->decodeCityIds($settings['pay_on_delivery_allowed_city_ids'] ?? '[]'),
            ];
        });
    }

    public function optionForCart(?Cart $cart, float $orderTotal, ?int $cityId = null): array
    {
        $settings = $this->settings();
        $reason = $this->unavailableReason($cart, $orderTotal, $cityId, $settings);

        return [
            'provider' => self::METHOD,
            'display_name' => 'Pay on Delivery',
            'description' => 'Pay when your order is delivered and collected by our team.',
            'sort_order' => 99,
            'mode' => 'manual',
            'available' => $reason === null,
            'unavailable_reason' => $reason,
            'settings' => [
                'min_order_amount' => $settings['pay_on_delivery_min_order_amount'],
                'max_order_amount' => $settings['pay_on_delivery_max_order_amount'],
                'allowed_city_ids' => $settings['pay_on_delivery_allowed_city_ids'],
            ],
        ];
    }

    public function assertCartEligible(?Cart $cart, float $orderTotal, ?int $cityId = null): void
    {
        $reason = $this->unavailableReason($cart, $orderTotal, $cityId, $this->settings());

        if ($reason !== null) {
            throw ValidationException::withMessages([
                'payment_mode' => [$reason],
            ]);
        }
    }

    protected function unavailableReason(?Cart $cart, float $orderTotal, ?int $cityId, array $settings): ?string
    {
        if (!$settings['pay_on_delivery_enabled']) {
            return 'Pay on delivery is not available right now.';
        }

        if (!$cart || $cart->items->isEmpty()) {
            return 'Your cart is empty.';
        }

        $hasIneligibleProduct = $cart->items->contains(function ($item) {
            return !((bool) ($item->sku?->product?->pay_on_delivery_enabled ?? false));
        });

        if ($hasIneligibleProduct) {
            return 'Pay on delivery is not available for one or more products in your cart.';
        }

        $minimum = (float) $settings['pay_on_delivery_min_order_amount'];
        if ($minimum > 0 && $orderTotal < $minimum) {
            return 'Pay on delivery is not available below the minimum order amount.';
        }

        $maximum = (float) $settings['pay_on_delivery_max_order_amount'];
        if ($maximum > 0 && $orderTotal > $maximum) {
            return 'Pay on delivery is not available above the maximum order amount.';
        }

        $allowedCityIds = $settings['pay_on_delivery_allowed_city_ids'];
        if (!empty($allowedCityIds) && (!$cityId || !in_array((int) $cityId, $allowedCityIds, true))) {
            return 'Pay on delivery is not available in the selected city.';
        }

        return null;
    }

    protected function decodeCityIds($raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('intval', $raw)));
        }

        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded)));
        }

        return collect(explode(',', (string) $raw))
            ->map(fn ($value) => (int) trim($value))
            ->filter()
            ->values()
            ->all();
    }

    protected function defaults(): array
    {
        return [
            'pay_on_delivery_enabled' => '0',
            'pay_on_delivery_min_order_amount' => '0',
            'pay_on_delivery_max_order_amount' => '0',
            'pay_on_delivery_allowed_city_ids' => '[]',
        ];
    }
}
