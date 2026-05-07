<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BrandSettingsService;
use App\Services\CurrencyFormatter;
use App\Services\HomepageSettingsService;
use App\Services\MailConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    /**
     * Get all settings
     */
    public function index(MailConfigurationService $mailConfiguration, BrandSettingsService $brandSettings, HomepageSettingsService $homepageSettings)
    {
        $settings = Cache::remember('site_settings', 3600, function () {
            return DB::table('settings')->pluck('value', 'key');
        });

        $settings = collect($settings)->map(function ($value, $key) {
            if ($this->isSensitiveSettingKey((string) $key)) {
                return null;
            }

            return $value;
        })->merge($brandSettings->publicSettings())
            ->merge($homepageSettings->publicSettings())
            ->merge($mailConfiguration->publicSettings());

        return response()->json($settings);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $this->normalizeBooleanInputs($request);

        $data = $request->validate([
            'site_name' => 'sometimes|string|max:255',
            'site_description' => 'sometimes|nullable|string',
            'site_email' => 'sometimes|nullable|email',
            'site_phone' => 'sometimes|nullable|string',
            'site_logo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'site_logo_path' => 'sometimes|nullable|string|max:1000',
            'theme_primary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_secondary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_tertiary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'homepage_flash_enabled' => 'sometimes|boolean',
            'homepage_flash_location' => 'sometimes|in:home,global,hidden',
            'homepage_flash_title' => 'sometimes|nullable|string|max:255',
            'homepage_flash_message' => 'sometimes|nullable|string|max:1000',
            'homepage_flash_highlight' => 'sometimes|nullable|string|max:255',
            'homepage_flash_button_label' => 'sometimes|nullable|string|max:100',
            'homepage_flash_button_url' => 'sometimes|nullable|string|max:1000',
            'homepage_flash_background_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'homepage_flash_text_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'homepage_flash_image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp,svg|max:4096',
            'homepage_flash_image_path' => 'sometimes|nullable|string|max:1000',
            'homepage_flash_countdown_enabled' => 'sometimes|boolean',
            'homepage_flash_countdown_target' => 'sometimes|nullable|date',
            'homepage_hero_enabled' => 'sometimes|boolean',
            'homepage_hero_layout' => 'sometimes|in:image_right,image_left,centered,full_bleed',
            'homepage_hero_eyebrow' => 'sometimes|nullable|string|max:100',
            'homepage_hero_headline' => 'sometimes|nullable|string|max:255',
            'homepage_hero_subheadline' => 'sometimes|nullable|string|max:1000',
            'homepage_hero_primary_button_label' => 'sometimes|nullable|string|max:100',
            'homepage_hero_primary_button_url' => 'sometimes|nullable|string|max:1000',
            'homepage_hero_secondary_button_label' => 'sometimes|nullable|string|max:100',
            'homepage_hero_secondary_button_url' => 'sometimes|nullable|string|max:1000',
            'homepage_hero_image' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp,svg|max:4096',
            'homepage_hero_image_path' => 'sometimes|nullable|string|max:1000',
            'homepage_hero_background_style' => 'sometimes|in:soft,solid,light,full_image',
            'currency' => 'sometimes|string|max:3',
            'currency_symbol' => 'sometimes|string|max:10',
            'currency_locale' => 'sometimes|nullable|string|max:20',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'enable_reviews' => 'sometimes|boolean',
            'enable_wishlist' => 'sometimes|boolean',
            'enable_coupons' => 'sometimes|boolean',
            'payment_gateway' => 'sometimes|string',
            'payment_gateway_config' => 'sometimes|array',
            'email_from_name' => 'sometimes|string',
            'email_from_address' => 'sometimes|email',
            'smtp_host' => 'sometimes|string',
            'smtp_port' => 'sometimes|integer',
            'smtp_username' => 'sometimes|string',
            'smtp_password' => 'sometimes|string',
            'smtp_encryption' => 'sometimes|in:tls,ssl,null',
            'mail_mode' => 'sometimes|in:auto,sandbox,live',
            'mail_sandbox_mailer' => 'sometimes|in:log,smtp',
            'mail_sandbox_host' => 'sometimes|nullable|string|max:255',
            'mail_sandbox_port' => 'sometimes|nullable|integer|min:1|max:65535',
            'mail_sandbox_username' => 'sometimes|nullable|string|max:255',
            'mail_sandbox_password' => 'sometimes|nullable|string|max:1000',
            'mail_sandbox_encryption' => 'sometimes|nullable|in:tls,ssl,null,none',
            'mail_sandbox_from_address' => 'sometimes|nullable|email|max:255',
            'mail_sandbox_from_name' => 'sometimes|nullable|string|max:255',
            'mail_live_mailer' => 'sometimes|in:log,smtp',
            'mail_live_host' => 'sometimes|nullable|string|max:255',
            'mail_live_port' => 'sometimes|nullable|integer|min:1|max:65535',
            'mail_live_username' => 'sometimes|nullable|string|max:255',
            'mail_live_password' => 'sometimes|nullable|string|max:1000',
            'mail_live_encryption' => 'sometimes|nullable|in:tls,ssl,null,none',
            'mail_live_from_address' => 'sometimes|nullable|email|max:255',
            'mail_live_from_name' => 'sometimes|nullable|string|max:255',
        ]);

        if ($request->hasFile('site_logo')) {
            $data['site_logo_path'] = $request->file('site_logo')->store('settings/branding', 'public');
            unset($data['site_logo']);
        }

        if ($request->hasFile('homepage_flash_image')) {
            $data['homepage_flash_image_path'] = $request->file('homepage_flash_image')->store('settings/homepage', 'public');
            unset($data['homepage_flash_image']);
        }

        if ($request->hasFile('homepage_hero_image')) {
            $data['homepage_hero_image_path'] = $request->file('homepage_hero_image')->store('settings/homepage', 'public');
            unset($data['homepage_hero_image']);
        }

        foreach ($data as $key => $value) {
            if ($this->isSensitiveSettingKey((string) $key) && (is_null($value) || (is_string($value) && trim($value) === ''))) {
                continue;
            }
            if ($this->isSensitiveSettingKey((string) $key)) {
                $value = Crypt::encryptString((string) $value);
            }

            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value, 'updated_at' => now()]
            );
        }

        // Clear cache
        Cache::forget('site_settings');
        Cache::forget('brand_settings');
        Cache::forget('homepage_settings');
        Cache::forget('currency_settings');

        return response()->json([
            'message' => 'Settings updated successfully'
        ]);
    }

    public function publicSettings(BrandSettingsService $brandSettings, HomepageSettingsService $homepageSettings, CurrencyFormatter $currencyFormatter)
    {
        return response()->json(array_merge(
            $brandSettings->publicSettings(),
            $homepageSettings->publicSettings(),
            ['currency' => $currencyFormatter->settings()]
        ));
    }

    public function testMail(Request $request, MailConfigurationService $mailConfiguration)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'mode' => 'nullable|in:auto,sandbox,live',
        ]);

        if (!empty($data['mode'])) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'mail_mode'],
                ['value' => $data['mode'], 'updated_at' => now(), 'created_at' => now()]
            );
            Cache::forget('site_settings');
            Cache::forget('brand_settings');
            Cache::forget('homepage_settings');
        }

        $mailConfiguration->apply();

        Mail::raw(
            "This is a test email from " . config('app.name') . ".\n\nActive mail mode: " . ($mailConfiguration->publicSettings()['active_mail_mode'] ?? 'unknown') . ".",
            function ($message) use ($data) {
                $message->to($data['email'])->subject('Test email from ' . config('app.name'));
            }
        );

        return response()->json([
            'message' => 'Test email sent successfully.',
            'active_mode' => $mailConfiguration->publicSettings()['active_mail_mode'] ?? null,
        ]);
    }

    /**
     * Get currency settings
     */
    public function getCurrency(CurrencyFormatter $currencyFormatter)
    {
        return response()->json($currencyFormatter->settings());
    }

    /**
     * Update currency
     */
    public function updateCurrency(Request $request)
    {
        $data = $request->validate([
            'currency' => 'required|string|max:3',
            'currency_symbol' => 'required|string|max:10',
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'currency'],
            ['value' => $data['currency'], 'updated_at' => now()]
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'currency_symbol'],
            ['value' => $data['currency_symbol'], 'updated_at' => now()]
        );

        Cache::forget('site_settings');
        Cache::forget('brand_settings');
        Cache::forget('homepage_settings');
        Cache::forget('currency_settings');

        return response()->json([
            'message' => 'Currency updated successfully'
        ]);
    }

    /**
     * Get payment gateway settings
     */
    public function getPaymentGateway()
    {
        $gateway = DB::table('settings')->where('key', 'payment_gateway')->value('value');
        $config = DB::table('settings')->where('key', 'payment_gateway_config')->value('value');

        return response()->json([
            'gateway' => $gateway,
            'config' => $config ? json_decode($config, true) : []
        ]);
    }

    /**
     * Update payment gateway
     */
    public function updatePaymentGateway(Request $request)
    {
        $data = $request->validate([
            'gateway' => 'required|in:paystack,flutterwave,stripe,paypal',
            'config' => 'required|array',
            'config.public_key' => 'required|string',
            'config.secret_key' => 'required|string',
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'payment_gateway'],
            ['value' => $data['gateway'], 'updated_at' => now()]
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'payment_gateway_config'],
            ['value' => json_encode($data['config']), 'updated_at' => now()]
        );

        Cache::forget('site_settings');
        Cache::forget('brand_settings');
        Cache::forget('homepage_settings');

        return response()->json([
            'message' => 'Payment gateway updated successfully'
        ]);
    }

    protected function isSensitiveSettingKey(string $key): bool
    {
        $needle = strtolower($key);
        return str_contains($needle, 'secret')
            || str_contains($needle, 'token')
            || str_contains($needle, 'password')
            || str_contains($needle, 'private');
    }

    protected function normalizeBooleanInputs(Request $request): void
    {
        $normalized = [];

        foreach ($this->booleanSettingKeys() as $key) {
            if ($request->has($key)) {
                $normalized[$key] = filter_var($request->input($key), FILTER_VALIDATE_BOOLEAN);
            }
        }

        if ($normalized !== []) {
            $request->merge($normalized);
        }
    }

    protected function booleanSettingKeys(): array
    {
        return [
            'enable_reviews',
            'enable_wishlist',
            'enable_coupons',
            'homepage_flash_enabled',
            'homepage_flash_countdown_enabled',
            'homepage_hero_enabled',
        ];
    }
}

