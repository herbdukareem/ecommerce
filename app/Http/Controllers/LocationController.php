<?php

namespace App\Http\Controllers;

use App\Models\LocationCity;
use App\Models\LocationCountry;
use App\Models\LocationState;
use App\Services\LocationAutocompleteService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(private readonly LocationAutocompleteService $autocompleteService)
    {
    }

    public function countries()
    {
        $countries = LocationCountry::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (LocationCountry $country) => [
                'code' => strtoupper($country->code),
                'name' => $country->name,
            ])
            ->values();

        return response()->json(['countries' => $countries]);
    }

    public function states(Request $request)
    {
        $validated = $request->validate([
            'country' => 'required|string|size:2',
        ]);

        $country = LocationCountry::query()
            ->whereRaw('LOWER(code) = ?', [strtolower($validated['country'])])
            ->firstOrFail();

        $states = LocationState::query()
            ->where('country_id', $country->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (LocationState $state) => [
                'code' => $state->code,
                'name' => $state->name,
            ])
            ->values();

        return response()->json([
            'country' => [
                'code' => strtoupper($country->code),
                'name' => $country->name,
            ],
            'states' => $states,
        ]);
    }

    public function cities(Request $request)
    {
        $validated = $request->validate([
            'country' => 'required|string|size:2',
            'state' => 'required|string|max:120',
        ]);

        $country = LocationCountry::query()
            ->whereRaw('LOWER(code) = ?', [strtolower($validated['country'])])
            ->firstOrFail();

        $stateLookup = strtolower($validated['state']);

        $state = LocationState::query()
            ->where('country_id', $country->id)
            ->where(function ($query) use ($stateLookup) {
                $query->whereRaw('LOWER(name) = ?', [$stateLookup])
                    ->orWhereRaw('LOWER(code) = ?', [$stateLookup]);
            })
            ->firstOrFail();

        $cities = LocationCity::query()
            ->where('state_id', $state->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (LocationCity $city) => [
                'code' => $city->code,
                'name' => $city->name,
            ])
            ->values();

        return response()->json([
            'country' => [
                'code' => strtoupper($country->code),
                'name' => $country->name,
            ],
            'state' => [
                'code' => $state->code,
                'name' => $state->name,
            ],
            'cities' => $cities,
        ]);
    }

    public function autocomplete(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:255',
            'type' => 'required|string|in:area_or_district,landmark',
            'country_code' => 'nullable|string|size:2',
            'country_name' => 'nullable|string|max:120',
            'state_name' => 'nullable|string|max:120',
            'city_name' => 'nullable|string|max:120',
        ]);

        $provider = (string) config('services.geo.autocomplete_provider', 'none');
        $configured = match ($provider) {
            'geoapify' => (string) config('services.geo.geoapify_key') !== '',
            'google_places' => (string) config('services.geo.google_places_key') !== '',
            default => false,
        };

        if (!$configured) {
            return response()->json([
                'configured' => false,
                'provider' => $provider,
                'suggestions' => [],
            ]);
        }

        $suggestions = $this->autocompleteService->suggest($validated);

        return response()->json([
            'configured' => true,
            'provider' => $provider,
            'suggestions' => $suggestions,
        ]);
    }
}
