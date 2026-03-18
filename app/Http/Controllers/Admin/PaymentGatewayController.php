<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends Controller
{
    public function __construct(protected PaymentGatewayManager $gatewayManager)
    {
    }

    public function index()
    {
        return response()->json([
            'gateways' => $this->gatewayManager->adminList(),
        ]);
    }

    public function update(Request $request, string $provider)
    {
        abort_unless(in_array($provider, PaymentGatewayManager::SUPPORTED_PROVIDERS, true), 404);

        $data = $request->validate([
            'display_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_enabled' => 'sometimes|boolean',
            'is_visible' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0|max:9999',
            'mode' => ['sometimes', Rule::in(['sandbox', 'live'])],
            'supported_currencies' => 'sometimes|array',
            'supported_currencies.*' => 'string|size:3',
            'fee_type' => ['nullable', Rule::in(['flat', 'percent'])],
            'fee_value' => 'nullable|numeric|min:0',
            'extra_config' => 'sometimes|array',
        ]);

        $gateway = PaymentGateway::query()->where('provider', $provider)->firstOrFail();

        if (array_key_exists('is_default', $data) && $data['is_default'] && array_key_exists('is_visible', $data) && !$data['is_visible']) {
            return response()->json(['message' => 'Default gateway must be visible at checkout.'], 422);
        }

        if ((array_key_exists('is_enabled', $data) && $data['is_enabled']) || ($gateway->is_enabled && !array_key_exists('is_enabled', $data))) {
            $nextMode = $data['mode'] ?? $gateway->mode;
            $readiness = $this->gatewayManager->readiness($provider, $nextMode);
            if (!$readiness['is_configured']) {
                return response()->json([
                    'message' => 'Gateway is not configured in environment variables.',
                    'readiness' => $readiness,
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($gateway, $data, $provider) {
                $gateway->update([
                    'display_name' => $data['display_name'] ?? $gateway->display_name,
                    'description' => array_key_exists('description', $data) ? $data['description'] : $gateway->description,
                    'is_enabled' => $data['is_enabled'] ?? $gateway->is_enabled,
                    'is_visible' => $data['is_visible'] ?? $gateway->is_visible,
                    'sort_order' => $data['sort_order'] ?? $gateway->sort_order,
                    'mode' => $data['mode'] ?? $gateway->mode,
                    'supported_currencies' => $data['supported_currencies'] ?? ($gateway->supported_currencies ?? ['NGN']),
                    'fee_type' => array_key_exists('fee_type', $data) ? $data['fee_type'] : $gateway->fee_type,
                    'fee_value' => array_key_exists('fee_value', $data) ? $data['fee_value'] : $gateway->fee_value,
                    'extra_config' => $data['extra_config'] ?? ($gateway->extra_config ?? []),
                ]);

                if (array_key_exists('is_default', $data) && $data['is_default']) {
                    $this->gatewayManager->setDefault($provider);
                }

                if (!$gateway->fresh()->is_enabled) {
                    $enabledCount = PaymentGateway::query()->where('is_enabled', true)->count();
                    if ($enabledCount === 0) {
                        throw new \InvalidArgumentException('At least one payment gateway must remain enabled.');
                    }
                }
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $gateway = PaymentGateway::query()->where('provider', $provider)->firstOrFail();

        return response()->json([
            'message' => 'Payment gateway updated successfully.',
            'gateway' => array_merge($gateway->toArray(), [
                'readiness' => $this->gatewayManager->readiness($gateway->provider, $gateway->mode),
            ]),
        ]);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'gateways' => 'required|array|min:1',
            'gateways.*.provider' => ['required', Rule::in(PaymentGatewayManager::SUPPORTED_PROVIDERS)],
            'gateways.*.sort_order' => 'required|integer|min:0|max:9999',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['gateways'] as $item) {
                PaymentGateway::query()
                    ->where('provider', $item['provider'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'message' => 'Payment gateway order updated successfully.',
            'gateways' => $this->gatewayManager->adminList(),
        ]);
    }

    public function setDefault(string $provider)
    {
        abort_unless(in_array($provider, PaymentGatewayManager::SUPPORTED_PROVIDERS, true), 404);

        try {
            $gateway = $this->gatewayManager->setDefault($provider);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Default gateway updated successfully.',
            'gateway' => array_merge($gateway->toArray(), [
                'readiness' => $this->gatewayManager->readiness($gateway->provider, $gateway->mode),
            ]),
        ]);
    }
}
