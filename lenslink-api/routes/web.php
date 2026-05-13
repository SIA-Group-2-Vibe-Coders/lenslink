<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to LensLink API',
        'status' => 'active',
        'version' => '1.0'
    ]);
});
