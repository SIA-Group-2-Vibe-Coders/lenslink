<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     * Grants access only to users with role_id = 1 (Admin).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role_id != 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden. Administrator access required.',
            ], 403);
        }

        return $next($request);
    }
}
