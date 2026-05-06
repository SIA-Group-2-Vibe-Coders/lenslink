<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Image;
// Note: In Laravel we didn't migrate login_logs table yet, so we skip it or mock it.
// We can use StorageLog for recent activity instead.

class AdminController extends Controller
{
    public function stats(Request $request)
    {
        if ($request->user()->role_id != 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Admin access required.'
            ], 403);
        }

        $totalUsers = User::count();
        $totalUploads = Image::where('status', 'active')->count();
        $storageUsage = Image::where('status', 'active')->sum('file_size');
        
        $recentUploads = Image::where('status', 'active')
                              ->orderBy('created_at', 'desc')
                              ->take(5)
                              ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_users' => $totalUsers,
                'total_uploads' => $totalUploads,
                'storage_usage_bytes' => $storageUsage,
                'recent_uploads' => $recentUploads,
                'failed_logins' => [] // Mocked as we didn't implement login_logs in Laravel
            ]
        ]);
    }
}
