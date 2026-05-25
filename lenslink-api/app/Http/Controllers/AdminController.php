<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Services\AdminService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(protected AdminService $adminService) {}

    /**
     * GET /admin-stats
     * Returns platform statistics for the admin dashboard.
     * Authorization is enforced by the `role.admin` middleware — no manual check needed.
     */
    public function stats(Request $request)
    {
        $stats = $this->adminService->getStats();

        return $this->successResponse($stats);
    }
}
