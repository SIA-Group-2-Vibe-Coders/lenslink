<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LocationService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google.maps_key');
    }

    /**
     * Convert an address string to GPS coordinates.
     */
    public function geocodeAddress(string $address)
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key'     => $this->apiKey,
        ]);

        if ($response->successful() && isset($response['results'][0])) {
            return $response['results'][0]['geometry']['location'];
        }

        return null;
    }

    /**
     * Get distance between two points (Matrix API).
     */
    public function getDistance(string $origin, string $destination)
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins'      => $origin,
            'destinations' => $destination,
            'key'          => $this->apiKey,
        ]);

        return $response->json();
    }
}
