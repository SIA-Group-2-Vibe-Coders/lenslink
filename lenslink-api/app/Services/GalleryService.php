<?php

namespace App\Services;

use App\Models\Gallery;
use App\Models\Album;
use App\Models\Image;
use Illuminate\Support\Collection;

class GalleryService
{
    /**
     * Get all galleries.
     */
    public function getAllGalleries(): Collection
    {
        return Gallery::all();
    }

    /**
     * Get albums for a specific gallery.
     */
    public function getAlbumsByGallery(int $galleryId): Collection
    {
        return Album::where('gallery_id', $galleryId)->get();
    }

    /**
     * Get active images for a specific album.
     */
    public function getImagesByAlbum(int $albumId): Collection
    {
        return Image::where('album_id', $albumId)
                    ->where('status', 'active')
                    ->get();
    }
}
