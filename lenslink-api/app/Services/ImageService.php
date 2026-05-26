<?php

namespace App\Services;

use App\Models\Image;
use App\Models\StorageLog;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;

class ImageService
{
    private string $cloudBase = 'https://res.cloudinary.com/dptmyksyl/image/upload';

    /**
     * Upload an image to Cloudinary and log the storage.
     */
    public function uploadImage(User $photographer, UploadedFile $file, int $albumId): array
    {
        // Upload original image to Cloudinary under lenslink/active folder
        $uploadResult = Cloudinary::uploadApi()->upload($file->getRealPath(), [
            'folder'        => 'lenslink/active',
            'resource_type' => 'image',
        ]);

        $publicId   = $uploadResult['public_id'];
        $originalUrl = $uploadResult['secure_url'];

        // Generate thumbnail URL (300px wide) using Cloudinary transformation
        $thumbnailUrl = str_replace('/upload/', '/upload/w_300,c_scale/', $originalUrl);

        // Generate watermarked URL using Cloudinary text overlay
        $watermarkedUrl = str_replace('/upload/', '/upload/l_text:Arial_48:LensLink%20Protected,g_center,o_50,co_white/', $originalUrl);

        $imageRecord = Image::create([
            'album_id'          => $albumId,
            'photographer_id'   => $photographer->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $publicId,
            'thumbnail_path'    => $thumbnailUrl,
            'watermarked_path'  => $watermarkedUrl,
            'file_size'         => $file->getSize(),
            'mime_type'         => $file->getMimeType(),
            'status'            => 'active',
        ]);

        StorageLog::create([
            'user_id'         => $photographer->id,
            'action'          => 'upload',
            'file_size_bytes' => $file->getSize(),
        ]);

        return [
            'id'              => $imageRecord->id,
            'url'             => $originalUrl,
            'thumbnail_url'   => $thumbnailUrl,
            'watermarked_url' => $watermarkedUrl,
        ];
    }

    /**
     * Archive an image.
     */
    public function archiveImage(User $photographer, int $imageId): void
    {
        $image = Image::where('id', $imageId)
                      ->where('photographer_id', $photographer->id)
                      ->firstOrFail();

        $oldPublicId = $image->file_path;

        // Move the image from active → archive folder in Cloudinary
        $newPublicId = str_replace('lenslink/active/', 'lenslink/archive/', $oldPublicId);

        $renameResult = Cloudinary::uploadApi()->rename($oldPublicId, $newPublicId);
        $newSecureUrl = $renameResult['secure_url'];

        $newThumbnailUrl = str_replace('/upload/', '/upload/w_300,c_scale/', $newSecureUrl);
        $newWatermarkedUrl = str_replace('/upload/', '/upload/l_text:Arial_48:LensLink%20Protected,g_center,o_50,co_white/', $newSecureUrl);

        $image->update([
            'status'            => 'archived',
            'file_path'         => $newPublicId,
            'thumbnail_path'    => $newThumbnailUrl,
            'watermarked_path'  => $newWatermarkedUrl,
        ]);

        StorageLog::create([
            'user_id'         => $photographer->id,
            'action'          => 'archive',
            'file_size_bytes' => $image->file_size,
        ]);
    }
}
