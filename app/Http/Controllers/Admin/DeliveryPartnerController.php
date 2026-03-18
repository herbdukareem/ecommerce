<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use Illuminate\Http\Request;

class DeliveryPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = DeliveryPartner::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('state')) {
            $state = strtolower($request->string('state'));
            $query->whereJsonContains('coverage_states', $state)
                ->orWhereJsonContains('coverage_states', (string) $request->string('state'));
        }

        if ($request->filled('city')) {
            $city = strtolower($request->string('city'));
            $query->whereJsonContains('coverage_cities', $city)
                ->orWhereJsonContains('coverage_cities', (string) $request->string('city'));
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $partner = DeliveryPartner::create($data);

        return response()->json([
            'message' => 'Delivery partner created successfully',
            'partner' => $partner,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $partner = DeliveryPartner::findOrFail($id);
        $data = $this->validatePayload($request, true);

        $partner->update($data);

        return response()->json([
            'message' => 'Delivery partner updated successfully',
            'partner' => $partner->fresh(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $partner = DeliveryPartner::findOrFail($id);
        $payload = $request->validate([
            'status' => 'required|string|in:active,inactive',
        ]);

        $partner->update(['status' => $payload['status']]);

        return response()->json([
            'message' => 'Delivery partner status updated',
            'partner' => $partner->fresh(),
        ]);
    }

    protected function validatePayload(Request $request, bool $isPartial = false): array
    {
        $required = $isPartial ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => "$required|string|max:120",
            'phone' => [
                $required,
                'string',
                'max:30',
                'regex:/^(?:\\+?234|0)[0-9]{10}$/',
            ],
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:150',
            'coverage_states' => 'nullable|array',
            'coverage_cities' => 'nullable|array',
            'coverage_areas' => 'nullable|array',
            'pricing_notes' => 'nullable|string|max:1000',
            'status' => 'sometimes|string|in:active,inactive',
            'vehicle_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        foreach (['coverage_states', 'coverage_cities', 'coverage_areas'] as $key) {
            if (array_key_exists($key, $data) && is_array($data[$key])) {
                $data[$key] = collect($data[$key])
                    ->map(fn ($value) => strtolower(trim((string) $value)))
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        return $data;
    }
}
