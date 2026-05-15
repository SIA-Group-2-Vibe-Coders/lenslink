<?php

namespace App\Http\Controllers;

use App\Services\PhotographerService;
use Illuminate\Http\Request;

class PhotographerController extends Controller
{
    protected $photographerService;
    protected $locationService;

    public function __construct(PhotographerService $photographerService, \App\Services\LocationService $locationService)
    {
        $this->photographerService = $photographerService;
        $this->locationService = $locationService;
    }

    /**
     * GET /photographers
     * List all photographers with their profile info.
     */
    public function index()
    {
        $photographers = $this->photographerService->getPhotographers();

        return response()->json([
            'status' => 'success',
            'data'   => $photographers,
        ]);
    }

    /**
     * GET /photographers/{id}
     * Get detailed profile of a photographer including their public galleries.
     */
    public function show($id)
    {
        $photographer = $this->photographerService->getPhotographerProfile($id);

        return response()->json([
            'status' => 'success',
            'data'   => $photographer,
        ]);
    }

    /**
     * GET /api/search/location
     * Search photographers using Google Maps Geocoding.
     */
    public function searchByLocation(Request $request)
    {
        $address = $request->query('address');
        if (!$address) return response()->json(['error' => 'Address required'], 400);

        $coords = $this->locationService->geocodeAddress($address);

        return response()->json([
            'status' => 'success',
            'search' => $address,
            'coordinates' => $coords,
            // In a real app, you would now filter photographers by distance in SQL
            'message' => 'Coordinates retrieved via Google Maps API'
        ]);
    }
}

