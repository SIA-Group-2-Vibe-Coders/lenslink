<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageRequest;
use App\Http\Traits\ApiResponse;
use App\Services\ImageService;

class ImageUploadController extends Controller
{
    use ApiResponse;

    public function __construct(protected ImageService $imageService) {}

    /**
     * POST /images/upload
     */
    public function upload(UploadImageRequest $request)
    {
        $data = $this->imageService->uploadImage(
            $request->user(),
            $request->file('image'),
            (int) $request->validated('album_id')
        );

        return $this->createdResponse($data, 'Image uploaded successfully.');
    }

    /**
     * POST /images/archive
     */
    public function archive(UploadImageRequest $request)
    {
        $request->validate(['id' => 'required|integer|exists:images,id']);

        $this->imageService->archiveImage($request->user(), (int) $request->id);

        return $this->successResponse(null, 'Image archived successfully.');
    }
}
