<?php

namespace App\Http\Controllers;


use App\Models\Address;
use App\Models\LocationCountry;
use App\Models\LocationState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return response()->json($addresses);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $user = $request->user();
        $address = DB::transaction(function () use ($data, $user) {
            $normalized = $this->normalizePayload($data);
            $normalized['user_id'] = $user->id;

            $setDefault = (bool) ($normalized['is_default'] ?? false)
                || !Address::where('user_id', $user->id)->exists();

            if ($setDefault) {
                Address::where('user_id', $user->id)->update(['is_default' => false]);
                $normalized['is_default'] = true;
            }

            return Address::create($normalized);
        });

        return response()->json([
            'message' => 'Address created successfully',
            'address' => $address,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $this->authorize('update', $address);

        $data = $request->validate($this->rules(true));

        $normalized = $this->normalizePayload($data);

        DB::transaction(function () use ($request, $address, $normalized) {
            if (($normalized['is_default'] ?? false) === true) {
                Address::where('user_id', $request->user()->id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($normalized);
        });

        return response()->json([
            'message' => 'Address updated successfully',
            'address' => $address->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $this->authorize('delete', $address);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $fallback = Address::where('user_id', $request->user()->id)->latest()->first();
            if ($fallback) {
                $fallback->update(['is_default' => true]);
            }
        }

        return response()->json([
            'message' => 'Address deleted successfully',
        ]);
    }

    public function setDefault(Request $request, int $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $this->authorize('update', $address);

        DB::transaction(function () use ($request, $address) {
            Address::where('user_id', $request->user()->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return response()->json([
            'message' => 'Default address updated successfully',
            'address' => $address->fresh(),
        ]);
    }

    protected function rules(bool $isPartial = false): array
    {
        $required = $isPartial ? 'sometimes' : 'required';

        return [
            'full_name' => "$required|string|max:255",
            'phone' => [
                $required,
                'string',
                'max:30',
                'regex:/^(?:\\+?234|0)[0-9]{10}$/',
            ],
            'email' => 'nullable|email|max:255',
            'country' => "$required|string|max:100",
            'country_code' => 'nullable|string|size:2',
            'country_name' => 'nullable|string|max:100',
            'state' => "$required|string|max:100",
            'state_code' => 'nullable|string|max:10',
            'state_name' => 'nullable|string|max:100',
            'city' => "$required|string|max:100",
            'city_name' => 'nullable|string|max:100',
            'area_or_district' => "$required|string|max:120",
            'address_line_1' => "$required|string|max:255",
            'address_line_2' => 'nullable|string|max:255',
            'landmark' => "$required|string|max:255",
            'postal_code' => 'nullable|string|max:30',
            'delivery_note' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'sometimes|boolean',
        ];
    }

    protected function normalizePayload(array $data): array
    {
        $country = null;

        $countryCode = strtolower((string) ($data['country_code'] ?? ''));
        if ($countryCode !== '') {
            $country = LocationCountry::query()
                ->whereRaw('LOWER(code) = ?', [$countryCode])
                ->first();
        }

        if (!$country) {
            $countryName = trim((string) ($data['country_name'] ?? $data['country'] ?? ''));
            if ($countryName !== '') {
                $country = LocationCountry::query()
                    ->whereRaw('LOWER(name) = ?', [strtolower($countryName)])
                    ->orWhereRaw('LOWER(code) = ?', [strtolower($countryName)])
                    ->first();
            }
        }

        if ($country) {
            $data['country_code'] = strtoupper($country->code);
            $data['country_name'] = $country->name;
            $data['country'] = $country->name;
        } else {
            if (!empty($data['country_code'])) {
                $data['country_code'] = strtoupper((string) $data['country_code']);
            }
            $data['country_name'] = $data['country_name'] ?? ($data['country'] ?? null);
        }

        $state = null;
        $stateLookup = strtolower(trim((string) ($data['state_code'] ?? $data['state_name'] ?? $data['state'] ?? '')));
        if ($stateLookup !== '' && $country) {
            $state = LocationState::query()
                ->where('country_id', $country->id)
                ->where(function ($query) use ($stateLookup) {
                    $query->whereRaw('LOWER(name) = ?', [$stateLookup])
                        ->orWhereRaw('LOWER(code) = ?', [$stateLookup]);
                })
                ->first();
        }

        if ($state) {
            $data['state_code'] = $state->code;
            $data['state_name'] = $state->name;
            $data['state'] = $state->name;
        } else {
            $data['state_name'] = $data['state_name'] ?? ($data['state'] ?? null);
        }

        $data['city_name'] = $data['city_name'] ?? ($data['city'] ?? null);
        $data['city'] = $data['city_name'] ?? $data['city'] ?? null;

        if (isset($data['full_name'])) {
            $data['name'] = $data['full_name'];
        }

        if (array_key_exists('address_line_1', $data)) {
            $data['line1'] = $data['address_line_1'];
        }
        if (array_key_exists('address_line_2', $data)) {
            $data['line2'] = $data['address_line_2'];
        }
        if (array_key_exists('postal_code', $data)) {
            $data['zip'] = $data['postal_code'];
        }
        if (array_key_exists('latitude', $data)) {
            $data['lat'] = $data['latitude'];
        }
        if (array_key_exists('longitude', $data)) {
            $data['lng'] = $data['longitude'];
        }

        return $data;
    }
}
