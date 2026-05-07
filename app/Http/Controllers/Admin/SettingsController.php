<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CurrencyFormatter;
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
    public function index(MailConfigurationService $mailConfiguration)
    {
        $settings = Cache::remember('site_settings', 3600, function () {
            return DB::table('settings')->pluck('value', 'key');
        });

        $settings = collect($settings)->map(function ($value, $key) {
            if ($this->isSensitiveSettingKey((string) $key)) {
                return null;
            }

            return $value;
        })->merge($mailConfiguration->publicSettings());

        return response()->json($settings);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => 'sometimes|string|max:255',
            'site_description' => 'sometimes|nullable|string',
            'site_email' => 'sometimes|nullable|email',
            'site_phone' => 'sometimes|nullable|string',
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
        Cache::forget('currency_settings');

        return response()->json([
            'message' => 'Settings updated successfully'
        ]);
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
}

