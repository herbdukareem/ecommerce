<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Get all settings
     */
    public function index()
    {
        $settings = Cache::remember('site_settings', 3600, function () {
            return DB::table('settings')->pluck('value', 'key');
        });

        return response()->json($settings);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => 'sometimes|string|max:255',
            'site_description' => 'sometimes|string',
            'site_email' => 'sometimes|email',
            'site_phone' => 'sometimes|string',
            'currency' => 'sometimes|string|max:3',
            'currency_symbol' => 'sometimes|string|max:10',
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
        ]);

        foreach ($data as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value, 'updated_at' => now()]
            );
        }

        // Clear cache
        Cache::forget('site_settings');

        return response()->json([
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Get currency settings
     */
    public function getCurrency()
    {
        $currency = [
            'code' => DB::table('settings')->where('key', 'currency')->value('value') ?? 'NGN',
            'symbol' => DB::table('settings')->where('key', 'currency_symbol')->value('value') ?? '₦',
        ];

        return response()->json($currency);
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
}

