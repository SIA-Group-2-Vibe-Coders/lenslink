<?php

namespace App\Services;

use App\Models\Gallery;
use App\Models\Album;
use App\Models\Image;
use Illuminate\Support\Collection;

class GalleryService
{
    /**
     * Get all galleries with optional photographer filtering and eager loaded albums.
     */
    public function getAllGalleries(?int $photographerId = null): Collection
    {
        $query = Gallery::with('albums');
        if ($photographerId) {
            $query->where('photographer_id', $photographerId);
        }
        return $query->get();
    }

    /**
     * Create a new gallery, automatically generating a default "Main Portfolio" album.
     */
    public function createGallery(array $data, int $photographerId): Gallery
    {
        $coverImageUrl = null;
        if (isset($data['cover_image']) && $data['cover_image'] instanceof \Illuminate\Http\UploadedFile) {
            $uploadResult = \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::uploadApi()->upload($data['cover_image']->getRealPath(), [
                'folder'        => 'lenslink/covers',
                'resource_type' => 'image',
            ]);
            $coverImageUrl = $uploadResult['secure_url'];
        }

        $gallery = Gallery::create([
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'cover_image'     => $coverImageUrl ?? 'https://via.placeholder.com/600x400?text=No+Cover+Image',
            'is_public'       => filter_var($data['is_public'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'photographer_id' => $photographerId,
            'client_id'       => $data['client_id'] ?? null,
        ]);

        // Automatically initialize a default Main Portfolio album
        Album::create([
            'title'      => 'Main Portfolio',
            'gallery_id' => $gallery->id,
        ]);

        return $gallery;
    }

    /**
     * Create a new album.
     */
    public function createAlbum(array $data): Album
    {
        return Album::create([
            'title'      => $data['title'],
            'gallery_id' => $data['gallery_id'],
        ]);
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
