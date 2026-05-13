<?php

namespace App\Services;

use App\Models\User;
use App\Models\Image;

class AdminService
{
    /**
     * Get overview statistics for the admin dashboard.
     */
    public function getStats(): array
    {
        $totalUsers = User::count();
        $totalUploads = Image::where('status', 'active')->count();
        $storageUsage = Image::where('status', 'active')->sum('file_size');
        
        $recentUploads = Image::where('status', 'active')
                              ->orderBy('created_at', 'desc')
                              ->take(5)
                              ->get();

        return [
            'total_users'         => $totalUsers,
            'total_uploads'       => $totalUploads,
            'storage_usage_bytes' => (int) $storageUsage,
            'recent_uploads'      => $recentUploads,
            'failed_logins'       => [] // Placeholder for future implementation
        ];
    }
}
