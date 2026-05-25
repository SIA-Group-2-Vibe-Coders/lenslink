<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumRequest;
use App\Http\Requests\StoreGalleryRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Gallery;
use App\Services\GalleryService;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    use ApiResponse;

    public function __construct(protected GalleryService $galleryService) {}

    /**
     * GET /gallery
     */
    public function galleries(Request $request)
    {
        $photographerId = $request->query('photographer_id');
        $galleries      = $this->galleryService->getAllGalleries($photographerId ? (int) $photographerId : null);

        return $this->successResponse($galleries);
    }

    /**
     * POST /gallery
     */
    public function storeGallery(StoreGalleryRequest $request)
    {
        $gallery = $this->galleryService->createGallery(
            $request->validated(),
            $request->user()->id
        );

        return $this->createdResponse($gallery, 'Gallery created successfully');
    }

    /**
     * POST /albums
     */
    public function storeAlbum(StoreAlbumRequest $request)
    {
        // Ensure the gallery belongs to the logged-in photographer
        $gallery = Gallery::findOrFail($request->validated('gallery_id'));

        if ($gallery->photographer_id !== $request->user()->id) {
            return $this->forbiddenResponse('You are not authorized to add albums to this gallery.');
        }

        $album = $this->galleryService->createAlbum($request->validated());

        return $this->createdResponse($album, 'Album created successfully');
    }

    /**
     * GET /albums?gallery_id=X
     */
    public function albums(Request $request)
    {
        $request->validate(['gallery_id' => 'nullable|integer|exists:galleries,id']);

        $albums = $this->galleryService->getAlbumsByGallery(
            $request->gallery_id ? (int) $request->gallery_id : null
        );

        return $this->successResponse($albums);
    }

    /**
     * GET /images?album_id=Y
     */
    public function images(Request $request)
    {
        $request->validate(['album_id' => 'nullable|integer|exists:albums,id']);

        $images = $this->galleryService->getImagesByAlbum(
            $request->album_id ? (int) $request->album_id : null
        );

        return $this->successResponse($images);
    }
}
