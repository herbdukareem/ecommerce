<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReferralSettingsService
{
    public function settings(): array
    {
        return Cache::remember('referral_settings', 300, function () {
            $settings = DB::table('settings')
                ->whereIn('key', array_keys($this->defaults()))
                ->pluck('value', 'key')
                ->toArray();

            return [
                'referral_enabled' => filter_var($settings['referral_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'referral_reward_basis' => (string) ($settings['referral_reward_basis'] ?? 'fixed'),
                'referral_reward_value' => (float) ($settings['referral_reward_value'] ?? 0),
                'referral_first_order_only' => filter_var($settings['referral_first_order_only'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'referral_minimum_order_amount' => (float) ($settings['referral_minimum_order_amount'] ?? 0),
                'referral_trigger' => (string) ($settings['referral_trigger'] ?? 'paid_order'),
                'referral_approval_mode' => (string) ($settings['referral_approval_mode'] ?? 'manual'),
                'referral_wallet_redemption_enabled' => filter_var($settings['referral_wallet_redemption_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        });
    }

    public function update(array $payload): array
    {
        $allowed = $this->defaults();

        foreach (array_intersect_key($payload, $allowed) as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        Cache::forget('referral_settings');
        Cache::forget('site_settings');

        return $this->settings();
    }

    protected function defaults(): array
    {
        return [
            'referral_enabled' => '0',
            'referral_reward_basis' => 'fixed',
            'referral_reward_value' => '0',
            'referral_first_order_only' => '1',
            'referral_minimum_order_amount' => '0',
            'referral_trigger' => 'paid_order',
            'referral_approval_mode' => 'manual',
            'referral_wallet_redemption_enabled' => '0',
        ];
    }
}
