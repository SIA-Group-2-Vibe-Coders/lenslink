<?php

namespace App\Http\Controllers;

use App\Services\AdminService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * GET /admin-stats
     */
    public function stats(Request $request)
    {
        if ($request->user()->role_id != 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden. Admin access required.'
            ], 403);
        }

        $stats = $this->adminService->getStats();

        return response()->json([
            'status' => 'success',
            'data'   => $stats,
        ]);
    }
}

