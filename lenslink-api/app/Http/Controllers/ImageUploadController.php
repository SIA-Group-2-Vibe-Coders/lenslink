<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImageService;

class ImageUploadController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'album_id' => 'required|exists:albums,id',
            'image'    => 'required|image|mimes:jpeg,png,webp|max:10240', // 10MB max
        ]);

        $data = $this->imageService->uploadImage(
            $request->user(),
            $request->file('image'),
            $request->album_id
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Image uploaded successfully.',
            'data'    => $data,
        ]);
    }

    public function archive(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:images,id',
        ]);

        $this->imageService->archiveImage($request->user(), $request->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Image archived successfully.',
        ]);
    }
}

