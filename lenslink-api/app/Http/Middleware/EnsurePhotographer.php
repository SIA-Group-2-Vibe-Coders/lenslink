<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhotographer
{
    /**
     * Handle an incoming request.
     * Grants access to authenticated users with any valid platform role:
     *   - 1 = Admin
     *   - 2 = Photographer
     *   - 3 = Client
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Reject any unrecognized role
        if (!in_array($user->role_id, [1, 2, 3])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden. Photographer access required.',
            ], 403);
        }

        return $next($request);
    }
}
