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
    public function galleries()
    {
        $galleries = $this->galleryService->getAllGalleries();

        return response()->json([
            'status' => 'success',
            'data'   => $galleries
        ]);
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

