<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PhotographerController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Public gallery routes
Route::get('/gallery', [GalleryController::class, 'galleries']);
Route::get('/albums', [GalleryController::class, 'albums']);
Route::get('/images', [GalleryController::class, 'images']);

// Photographer Discovery
Route::get('/photographers', [PhotographerController::class, 'index']);
Route::get('/photographers/{id}', [PhotographerController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Image Uploads (Photographer)
    Route::post('/upload', [ImageUploadController::class, 'upload']);
    Route::delete('/delete-image', [ImageUploadController::class, 'archive']);

    // Admin
    Route::get('/admin-stats', [AdminController::class, 'stats']);

    // Chat
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/conversations', [MessageController::class, 'conversations']);

    // Stripe Payments
    Route::post('/bookings/intent', [\App\Http\Controllers\BookingController::class, 'createIntent']);

    // Google Maps Location Search
    Route::get('/search/location', [\App\Http\Controllers\PhotographerController::class, 'searchByLocation']);

    // Firebase External Auth Sync
    Route::post('/auth/firebase', [\App\Http\Controllers\AuthController::class, 'firebaseSync']);
});
