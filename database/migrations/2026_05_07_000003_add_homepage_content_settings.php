<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'homepage_flash_enabled' => 'true',
            'homepage_flash_location' => 'home',
            'homepage_flash_title' => 'Launching Soon',
            'homepage_flash_message' => 'Fresh deals, fast delivery, and everyday essentials are ready for you.',
            'homepage_flash_highlight' => 'Up to 60% off',
            'homepage_flash_button_label' => 'Shop Deals',
            'homepage_flash_button_url' => '/products',
            'homepage_flash_background_color' => '#063f7c',
            'homepage_flash_text_color' => '#ffffff',
            'homepage_flash_countdown_enabled' => 'false',
            'homepage_flash_countdown_target' => '',
            'homepage_hero_enabled' => 'true',
            'homepage_hero_layout' => 'image_right',
            'homepage_hero_eyebrow' => 'Online Mart',
            'homepage_hero_headline' => 'Discover quality products for every need',
            'homepage_hero_subheadline' => 'Shop trusted items with clear prices, easy checkout, and reliable delivery updates.',
            'homepage_hero_primary_button_label' => 'Shop Now',
            'homepage_hero_primary_button_url' => '/products',
            'homepage_hero_secondary_button_label' => 'View Deals',
            'homepage_hero_secondary_button_url' => '/products?sale=true',
            'homepage_hero_background_style' => 'soft',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'homepage_flash_enabled',
                'homepage_flash_location',
                'homepage_flash_title',
                'homepage_flash_message',
                'homepage_flash_highlight',
                'homepage_flash_button_label',
                'homepage_flash_button_url',
                'homepage_flash_background_color',
                'homepage_flash_text_color',
                'homepage_flash_image_path',
                'homepage_flash_countdown_enabled',
                'homepage_flash_countdown_target',
                'homepage_hero_enabled',
                'homepage_hero_layout',
                'homepage_hero_eyebrow',
                'homepage_hero_headline',
                'homepage_hero_subheadline',
                'homepage_hero_primary_button_label',
                'homepage_hero_primary_button_url',
                'homepage_hero_secondary_button_label',
                'homepage_hero_secondary_button_url',
                'homepage_hero_image_path',
                'homepage_hero_background_style',
            ])
            ->delete();
    }
};
