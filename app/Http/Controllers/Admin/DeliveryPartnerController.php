<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        if ($request->hasFile('contact_photo')) {
            $data['contact_photo_path'] = $request->file('contact_photo')->store('delivery-partners/contacts', 'public');
        }

        if ($request->hasFile('vehicle_image')) {
            $data['vehicle_image_path'] = $request->file('vehicle_image')->store('delivery-partners/vehicles', 'public');
        }

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

        if ($request->hasFile('contact_photo')) {
            if ($partner->contact_photo_path) {
                Storage::disk('public')->delete($partner->contact_photo_path);
            }
            $data['contact_photo_path'] = $request->file('contact_photo')->store('delivery-partners/contacts', 'public');
        }

        if ($request->hasFile('vehicle_image')) {
            if ($partner->vehicle_image_path) {
                Storage::disk('public')->delete($partner->vehicle_image_path);
            }
            $data['vehicle_image_path'] = $request->file('vehicle_image')->store('delivery-partners/vehicles', 'public');
        }

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
            'vehicle_type' => 'nullable|string|in:motorbike,bicycle,tricycle,car,van,truck',
            'contact_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'vehicle_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
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
