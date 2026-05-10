<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BrandSettingsService
{
    public function settings(): array
    {
        return Cache::store('array')->remember('brand_settings', 3600, function () {
            try {
                if (!Schema::hasTable('settings')) {
                    return $this->defaults();
                }

                $settings = DB::table('settings')->pluck('value', 'key')->toArray();
            } catch (\Throwable) {
                return $this->defaults();
            }

            return array_merge($this->defaults(), [
                'site_name' => (string) ($settings['site_name'] ?? $this->defaults()['site_name']),
                'site_description' => (string) ($settings['site_description'] ?? $this->defaults()['site_description']),
                'site_email' => (string) ($settings['site_email'] ?? ''),
                'site_phone' => (string) ($settings['site_phone'] ?? ''),
                'site_whatsapp_number' => (string) ($settings['site_whatsapp_number'] ?? ''),
                'site_logo_path' => (string) ($settings['site_logo_path'] ?? $this->defaults()['site_logo_path']),
                'theme_primary_color' => (string) ($settings['theme_primary_color'] ?? $this->defaults()['theme_primary_color']),
                'theme_secondary_color' => (string) ($settings['theme_secondary_color'] ?? $this->defaults()['theme_secondary_color']),
                'theme_tertiary_color' => (string) ($settings['theme_tertiary_color'] ?? $this->defaults()['theme_tertiary_color']),
            ]);
        });
    }

    public function publicSettings(): array
    {
        $settings = $this->settings();

        return array_merge($settings, [
            'site_logo_url' => $this->logoUrl($settings['site_logo_path'] ?? null),
        ]);
    }

    public function apply(): void
    {
        $settings = $this->settings();

        config([
            'app.name' => $settings['site_name'] ?: config('app.name'),
        ]);
    }

    protected function logoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    protected function defaults(): array
    {
        return [
            'site_name' => 'Online Mart',
            'site_description' => 'Your one-stop shop for quality products, fast delivery, and everyday value.',
            'site_email' => '',
            'site_phone' => '',
            'site_whatsapp_number' => '',
            'site_logo_path' => '/images/online-mart-logo.png',
            'theme_primary_color' => '#063f7c',
            'theme_secondary_color' => '#43b02a',
            'theme_tertiary_color' => '#f59e0b',
        ];
    }
}
