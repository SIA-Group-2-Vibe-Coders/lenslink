<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class PhotographerService
{
    /**
     * Get all photographers.
     */
    public function getPhotographers(): Collection
    {
        return User::where('role_id', 2)
            ->select('id', 'name', 'avatar', 'bio', 'specialty', 'location', 'price_range')
            ->get();
    }

    /**
     * Get a photographer's profile with their galleries.
     */
    public function getPhotographerProfile(int $id): User
    {
        return User::where('role_id', 2)
            ->with(['galleries' => function ($query) {
                $query->select('id', 'photographer_id', 'title', 'cover_image as cover_image_url', 'description', 'created_at');
            }])
            ->findOrFail($id);
    }
}
