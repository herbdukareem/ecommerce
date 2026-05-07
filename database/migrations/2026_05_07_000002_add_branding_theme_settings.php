<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'site_name' => 'Online Mart',
            'site_description' => 'Your one-stop shop for quality products, fast delivery, and everyday value.',
            'site_logo_path' => '/images/online-mart-logo.png',
            'theme_primary_color' => '#063f7c',
            'theme_secondary_color' => '#43b02a',
            'theme_tertiary_color' => '#f59e0b',
            'mail_sandbox_from_name' => 'Online Mart',
            'mail_live_from_name' => 'Online Mart',
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
                'site_logo_path',
                'theme_primary_color',
                'theme_secondary_color',
                'theme_tertiary_color',
            ])
            ->delete();
    }
};
