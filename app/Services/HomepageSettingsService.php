<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HomepageSettingsService
{
    public function settings(): array
    {
        return Cache::remember('homepage_settings', 3600, function () {
            if (!Schema::hasTable('settings')) {
                return $this->defaults();
            }

            $settings = DB::table('settings')->pluck('value', 'key')->toArray();

            return array_merge($this->defaults(), [
                'homepage_flash_enabled' => $this->bool($settings['homepage_flash_enabled'] ?? true),
                'homepage_flash_location' => (string) ($settings['homepage_flash_location'] ?? 'home'),
                'homepage_flash_title' => (string) ($settings['homepage_flash_title'] ?? $this->defaults()['homepage_flash_title']),
                'homepage_flash_message' => (string) ($settings['homepage_flash_message'] ?? $this->defaults()['homepage_flash_message']),
                'homepage_flash_highlight' => (string) ($settings['homepage_flash_highlight'] ?? $this->defaults()['homepage_flash_highlight']),
                'homepage_flash_button_label' => (string) ($settings['homepage_flash_button_label'] ?? $this->defaults()['homepage_flash_button_label']),
                'homepage_flash_button_url' => (string) ($settings['homepage_flash_button_url'] ?? $this->defaults()['homepage_flash_button_url']),
                'homepage_flash_background_color' => (string) ($settings['homepage_flash_background_color'] ?? $this->defaults()['homepage_flash_background_color']),
                'homepage_flash_text_color' => (string) ($settings['homepage_flash_text_color'] ?? $this->defaults()['homepage_flash_text_color']),
                'homepage_flash_image_path' => (string) ($settings['homepage_flash_image_path'] ?? ''),
                'homepage_flash_countdown_enabled' => $this->bool($settings['homepage_flash_countdown_enabled'] ?? false),
                'homepage_flash_countdown_target' => (string) ($settings['homepage_flash_countdown_target'] ?? ''),
                'homepage_hero_enabled' => $this->bool($settings['homepage_hero_enabled'] ?? true),
                'homepage_hero_layout' => (string) ($settings['homepage_hero_layout'] ?? 'image_right'),
                'homepage_hero_eyebrow' => (string) ($settings['homepage_hero_eyebrow'] ?? $this->defaults()['homepage_hero_eyebrow']),
                'homepage_hero_headline' => (string) ($settings['homepage_hero_headline'] ?? $this->defaults()['homepage_hero_headline']),
                'homepage_hero_subheadline' => (string) ($settings['homepage_hero_subheadline'] ?? $this->defaults()['homepage_hero_subheadline']),
                'homepage_hero_primary_button_label' => (string) ($settings['homepage_hero_primary_button_label'] ?? $this->defaults()['homepage_hero_primary_button_label']),
                'homepage_hero_primary_button_url' => (string) ($settings['homepage_hero_primary_button_url'] ?? $this->defaults()['homepage_hero_primary_button_url']),
                'homepage_hero_secondary_button_label' => (string) ($settings['homepage_hero_secondary_button_label'] ?? $this->defaults()['homepage_hero_secondary_button_label']),
                'homepage_hero_secondary_button_url' => (string) ($settings['homepage_hero_secondary_button_url'] ?? $this->defaults()['homepage_hero_secondary_button_url']),
                'homepage_hero_image_path' => (string) ($settings['homepage_hero_image_path'] ?? ''),
                'homepage_hero_background_style' => (string) ($settings['homepage_hero_background_style'] ?? 'soft'),
            ]);
        });
    }

    public function publicSettings(): array
    {
        $settings = $this->settings();

        return array_merge($settings, [
            'homepage_flash_image_url' => $this->imageUrl($settings['homepage_flash_image_path'] ?? null),
            'homepage_hero_image_url' => $this->imageUrl($settings['homepage_hero_image_path'] ?? null),
        ]);
    }

    protected function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    protected function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function defaults(): array
    {
        return [
            'homepage_flash_enabled' => true,
            'homepage_flash_location' => 'home',
            'homepage_flash_title' => 'Launching Soon',
            'homepage_flash_message' => 'Fresh deals, fast delivery, and everyday essentials are ready for you.',
            'homepage_flash_highlight' => 'Up to 60% off',
            'homepage_flash_button_label' => 'Shop Deals',
            'homepage_flash_button_url' => '/products',
            'homepage_flash_background_color' => '#063f7c',
            'homepage_flash_text_color' => '#ffffff',
            'homepage_flash_image_path' => '',
            'homepage_flash_countdown_enabled' => false,
            'homepage_flash_countdown_target' => '',
            'homepage_hero_enabled' => true,
            'homepage_hero_layout' => 'image_right',
            'homepage_hero_eyebrow' => 'Online Mart',
            'homepage_hero_headline' => 'Discover quality products for every need',
            'homepage_hero_subheadline' => 'Shop trusted items with clear prices, easy checkout, and reliable delivery updates.',
            'homepage_hero_primary_button_label' => 'Shop Now',
            'homepage_hero_primary_button_url' => '/products',
            'homepage_hero_secondary_button_label' => 'View Deals',
            'homepage_hero_secondary_button_url' => '/products?sale=true',
            'homepage_hero_image_path' => '',
            'homepage_hero_background_style' => 'soft',
        ];
    }
}
