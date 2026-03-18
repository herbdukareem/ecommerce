<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationAutocompleteService
{
    public function suggest(array $payload): array
    {
        $query = trim((string) ($payload['query'] ?? ''));
        if (strlen($query) < 2) {
            return [];
        }

        $provider = (string) config('services.geo.autocomplete_provider', 'none');

        return match ($provider) {
            'geoapify' => $this->geoapifySuggestions($payload),
            'google_places' => $this->googlePlacesSuggestions($payload),
            default => [],
        };
    }

    protected function geoapifySuggestions(array $payload): array
    {
        $key = (string) config('services.geo.geoapify_key');
        if ($key === '') {
            return [];
        }

        $query = trim((string) ($payload['query'] ?? ''));
        $countryCode = strtolower((string) ($payload['country_code'] ?? ''));
        $countryName = strtolower((string) ($payload['country_name'] ?? ''));
        $state = strtolower((string) ($payload['state_name'] ?? ''));
        $city = strtolower((string) ($payload['city_name'] ?? ''));

        try {
            $params = [
                'text' => $query,
                'limit' => 7,
                'apiKey' => $key,
            ];

            if ($countryCode !== '') {
                $params['filter'] = 'countrycode:' . $countryCode;
            }

            $response = Http::timeout(6)->get('https://api.geoapify.com/v1/geocode/autocomplete', $params);
            if (!$response->successful()) {
                return [];
            }

            return collect($response->json('features', []))
                ->map(function ($feature) use ($countryCode, $countryName, $state, $city) {
                    $properties = Arr::get($feature, 'properties', []);
                    $country = strtolower((string) Arr::get($properties, 'country_code'));
                    $stateName = strtolower((string) Arr::get($properties, 'state'));
                    $cityName = strtolower((string) Arr::get($properties, 'city'));

                    if ($countryCode !== '' && $country !== $countryCode) {
                        return null;
                    }

                    if ($countryName !== '' && $countryCode === '' && !str_contains(strtolower((string) Arr::get($properties, 'country', '')), $countryName)) {
                        return null;
                    }

                    if ($state !== '' && $stateName !== '' && !str_contains($stateName, $state)) {
                        return null;
                    }

                    if ($city !== '' && $cityName !== '' && !str_contains($cityName, $city)) {
                        return null;
                    }

                    return [
                        'label' => (string) Arr::get($properties, 'formatted', ''),
                        'value' => (string) (Arr::get($properties, 'suburb') ?: Arr::get($properties, 'district') ?: Arr::get($properties, 'name') ?: Arr::get($properties, 'formatted', '')),
                        'latitude' => Arr::get($properties, 'lat'),
                        'longitude' => Arr::get($properties, 'lon'),
                    ];
                })
                ->filter(fn ($item) => !empty($item['value']))
                ->unique('value')
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            Log::warning('geoapify_autocomplete_failed', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    protected function googlePlacesSuggestions(array $payload): array
    {
        $key = (string) config('services.geo.google_places_key');
        if ($key === '') {
            return [];
        }

        $query = trim((string) ($payload['query'] ?? ''));
        $countryCode = strtolower((string) ($payload['country_code'] ?? ''));

        try {
            $params = [
                'input' => $query,
                'key' => $key,
            ];

            if ($countryCode !== '') {
                $params['components'] = 'country:' . $countryCode;
            }

            $response = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', $params);
            if (!$response->successful()) {
                return [];
            }

            return collect($response->json('predictions', []))
                ->map(function ($prediction) {
                    return [
                        'label' => (string) Arr::get($prediction, 'description', ''),
                        'value' => (string) Arr::get($prediction, 'structured_formatting.main_text', Arr::get($prediction, 'description', '')),
                        'place_id' => (string) Arr::get($prediction, 'place_id', ''),
                    ];
                })
                ->filter(fn ($item) => !empty($item['value']))
                ->unique('value')
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            Log::warning('google_places_autocomplete_failed', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
