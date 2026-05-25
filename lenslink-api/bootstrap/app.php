<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS / Private Network access
        $middleware->prepend(\App\Http\Middleware\HandlePrivateNetworkAccess::class);

        // Register role-based middleware aliases
        $middleware->alias([
            'role.admin'        => \App\Http\Middleware\EnsureAdmin::class,
            'role.photographer' => \App\Http\Middleware\EnsurePhotographer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON 401 for unauthenticated API requests (instead of HTML redirect)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated. Please log in to access this resource.',
                ], 401);
            }
        });
    })->create();
