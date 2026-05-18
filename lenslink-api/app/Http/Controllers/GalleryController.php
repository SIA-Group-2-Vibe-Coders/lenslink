<?php

namespace App\Http\Controllers;

use App\Services\GalleryService;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    protected $galleryService;

    public function __construct(GalleryService $galleryService)
    {
        $this->galleryService = $galleryService;
    }

    /**
     * GET /gallery
     */
    public function galleries(Request $request)
    {
        $photographerId = $request->query('photographer_id');
        $galleries = $this->galleryService->getAllGalleries($photographerId ? (int)$photographerId : null);

        return response()->json([
            'status' => 'success',
            'data'   => $galleries
        ]);
    }

    /**
     * POST /gallery
     */
    public function storeGallery(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public'   => 'nullable',
            'cover_image' => 'nullable|image|mimes:jpeg,png,webp|max:5120',
            'client_id'   => 'nullable|exists:users,id',
        ]);

        $photographer = $request->user();

        $gallery = $this->galleryService->createGallery(
            $request->all(),
            $photographer->id
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Gallery created successfully',
            'data'    => $gallery
        ], 201);
    }

    /**
     * POST /albums
     */
    public function storeAlbum(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'gallery_id' => 'required|exists:galleries,id',
        ]);

        // Ensure the gallery belongs to the logged-in photographer
        $gallery = \App\Models\Gallery::findOrFail($request->gallery_id);
        if ($gallery->photographer_id !== $request->user()->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized to add albums to this gallery'
            ], 403);
        }

        $album = $this->galleryService->createAlbum($request->all());

        return response()->json([
            'status'  => 'success',
            'message' => 'Album created successfully',
            'data'    => $album
        ], 201);
    }

    /**
     * GET /albums?gallery_id=X
     */
    public function albums(Request $request)
    {
        $request->validate([
            'gallery_id' => 'required|exists:galleries,id'
        ]);

        $albums = $this->galleryService->getAlbumsByGallery($request->gallery_id);

        return response()->json([
            'status' => 'success',
            'data'   => $albums
        ]);
    }

    /**
     * GET /images?album_id=Y
     */
    public function images(Request $request)
    {
        $request->validate([
            'album_id' => 'required|exists:albums,id'
        ]);

        $images = $this->galleryService->getImagesByAlbum($request->album_id);

        return response()->json([
            'status' => 'success',
            'data'   => $images
        ]);
    }
}

