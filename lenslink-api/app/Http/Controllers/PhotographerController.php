<?php

namespace App\Http\Controllers;

use App\Services\PhotographerService;
use Illuminate\Http\Request;

class PhotographerController extends Controller
{
    protected $photographerService;

    public function __construct(PhotographerService $photographerService)
    {
        $this->photographerService = $photographerService;
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
}

