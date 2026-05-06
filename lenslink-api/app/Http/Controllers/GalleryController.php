<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gallery;
use App\Models\Album;
use App\Models\Image;

class GalleryController extends Controller
{
    public function galleries()
    {
        $galleries = Gallery::all();
        return response()->json([
            'status' => 'success',
            'data' => $galleries
        ]);
    }

    public function albums(Request $request)
    {
        $request->validate([
            'gallery_id' => 'required|exists:galleries,id'
        ]);

        $albums = Album::where('gallery_id', $request->gallery_id)->get();
        return response()->json([
            'status' => 'success',
            'data' => $albums
        ]);
    }

    public function images(Request $request)
    {
        $request->validate([
            'album_id' => 'required|exists:albums,id'
        ]);

        $images = Image::where('album_id', $request->album_id)
                       ->where('status', 'active')
                       ->get();

        return response()->json([
            'status' => 'success',
            'data' => $images
        ]);
    }
}
