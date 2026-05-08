<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CurrencyFormatter
{
    /**
     * @return array{code:string,symbol:string,precision:int,locale:string}
     */
    public function settings(): array
    {
        return Cache::remember('currency_settings', 3600, function () {
            try {
                if (!Schema::hasTable('settings')) {
                    return $this->defaults();
                }

                $settings = DB::table('settings')
                    ->whereIn('key', ['currency', 'currency_symbol', 'currency_precision', 'currency_locale'])
                    ->pluck('value', 'key');
            } catch (\Throwable) {
                return $this->defaults();
            }

            return [
                'code' => (string) ($settings['currency'] ?? 'NGN'),
                'symbol' => (string) ($settings['currency_symbol'] ?? 'NGN'),
                'precision' => (int) ($settings['currency_precision'] ?? 2),
                'locale' => (string) ($settings['currency_locale'] ?? 'en-NG'),
            ];
        });
    }

    public function format(float|int|string|null $amount): string
    {
        $settings = $this->settings();
        $number = number_format((float) ($amount ?? 0), $settings['precision']);

        return trim($settings['symbol'] . $number);
    }

    protected function defaults(): array
    {
        return [
            'code' => 'NGN',
            'symbol' => 'NGN',
            'precision' => 2,
            'locale' => 'en-NG',
        ];
    }
}
