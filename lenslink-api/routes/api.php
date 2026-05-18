<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PhotographerController;
use App\Http\Controllers\PostController;

// Public Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/auth/firebase', [AuthController::class, 'firebaseSync']); // Public: exchanges Firebase token for API token

// Public gallery and photographer routes
Route::get('/gallery', [GalleryController::class, 'galleries']);
Route::get('/albums', [GalleryController::class, 'albums']);
Route::get('/images', [GalleryController::class, 'images']);
Route::get('/photographers', [PhotographerController::class, 'index']);
Route::get('/photographers/{id}', [PhotographerController::class, 'show']);
Route::get('/posts', [PostController::class, 'index']);

// Google Maps Location Search
Route::get('/search/location', [PhotographerController::class, 'searchByLocation']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);


    // Admin routes
    Route::get('/admin-stats', [AdminController::class, 'stats']);

    // Messaging routes
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/conversations', [MessageController::class, 'conversations']);

    // Image Management
    Route::post('/images/upload', [ImageUploadController::class, 'upload']);
    Route::post('/images/archive', [ImageUploadController::class, 'archive']);

    // Gallery & Album Management
    Route::post('/gallery', [GalleryController::class, 'storeGallery']);
    Route::post('/albums', [GalleryController::class, 'storeAlbum']);

    // Profile Update
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // Social Posts
    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::post('/posts/{id}/like', [PostController::class, 'toggleLike']);
    Route::post('/posts/{id}/comment', [PostController::class, 'addComment']);

    // Payments/Bookings
    Route::post('/bookings/intent', [\App\Http\Controllers\BookingController::class, 'createIntent']);
    Route::get('/bookings', [\App\Http\Controllers\BookingController::class, 'index']);
    Route::get('/bookings/{id}', [\App\Http\Controllers\BookingController::class, 'show']);
    Route::post('/bookings', [\App\Http\Controllers\BookingController::class, 'store']);
    Route::patch('/bookings/{id}/status', [\App\Http\Controllers\BookingController::class, 'updateStatus']);
    Route::post('/bookings/{id}/pay', [\App\Http\Controllers\BookingController::class, 'pay']);
    Route::post('/bookings/{id}/confirm', [\App\Http\Controllers\BookingController::class, 'confirmPayment']);
});
