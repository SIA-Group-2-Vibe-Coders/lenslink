<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Models\StorageLog;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'album_id' => 'required|exists:albums,id',
            'image' => 'required|image|mimes:jpeg,png,webp|max:10240', // 10MB max
        ]);

        $file = $request->file('image');
        $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $file->getClientOriginalName());
        
        $activePath = 'public/active_storage/' . $filename;
        $thumbPath = 'public/thumbnails/' . $filename;
        $watermarkPath = 'public/watermarked/' . $filename;

        // Save original
        Storage::put($activePath, file_get_contents($file));

        $manager = new ImageManager(new Driver());

        // Create Thumbnail
        $thumbImage = $manager->read($file)->scale(width: 300);
        Storage::put($thumbPath, (string) $thumbImage->encode());

        // Create Watermark (Simplified approach: lower opacity, text overlay)
        $watermarkedImage = $manager->read($file);
        $watermarkedImage->text('LensLink Protected', $watermarkedImage->width() / 2, $watermarkedImage->height() / 2, function($font) {
            $font->size(48);
            $font->color([255, 255, 255, 0.5]);
            $font->align('center');
            $font->valign('middle');
        });
        Storage::put($watermarkPath, (string) $watermarkedImage->encode());

        $imageRecord = Image::create([
            'album_id' => $request->album_id,
            'photographer_id' => $request->user()->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => 'active_storage/' . $filename,
            'thumbnail_path' => 'thumbnails/' . $filename,
            'watermarked_path' => 'watermarked/' . $filename,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'active',
        ]);

        StorageLog::create([
            'user_id' => $request->user()->id,
            'action' => 'upload',
            'file_size_bytes' => $file->getSize()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Image uploaded successfully.',
            'data' => [
                'id' => $imageRecord->id,
                'path' => Storage::url($imageRecord->file_path)
            ]
        ]);
    }

    public function archive(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:images,id'
        ]);

        $image = Image::where('id', $request->id)
                      ->where('photographer_id', $request->user()->id)
                      ->firstOrFail();

        $filename = basename($image->file_path);
        
        if (Storage::exists('public/' . $image->file_path)) {
            Storage::move('public/' . $image->file_path, 'public/archive_storage/' . $filename);
        }

        $image->update([
            'status' => 'archived',
            'file_path' => 'archive_storage/' . $filename
        ]);

        StorageLog::create([
            'user_id' => $request->user()->id,
            'action' => 'archive',
            'file_size_bytes' => $image->file_size
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Image archived successfully.'
        ]);
    }
}
