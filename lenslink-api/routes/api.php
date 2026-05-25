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
use App\Http\Controllers\BookingController;

/*
|--------------------------------------------------------------------------
| Public Routes — No authentication required
|--------------------------------------------------------------------------
*/

// Auth — rate-limited to prevent brute force
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login',    [AuthController::class, 'login'])->name('login');
    Route::post('/auth/firebase', [AuthController::class, 'firebaseSync'])->name('auth.firebase');
});

// Public read-only resources
Route::get('/gallery',              [GalleryController::class, 'galleries']);
Route::get('/albums',               [GalleryController::class, 'albums']);
Route::get('/images',               [GalleryController::class, 'images']);
Route::get('/photographers',        [PhotographerController::class, 'index']);
Route::get('/photographers/{id}',   [PhotographerController::class, 'show']);
Route::get('/posts',                [PostController::class, 'index']);
Route::get('/search/location',      [PhotographerController::class, 'searchByLocation']);

/*
|--------------------------------------------------------------------------
| Protected Routes — Require valid Sanctum token
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- Auth / Profile ---
    Route::get('/profile',         [AuthController::class, 'profile']);
    Route::post('/logout',         [AuthController::class, 'logout']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // --- Social Feed ---
    Route::post('/posts',                [PostController::class, 'store']);
    Route::delete('/posts/{id}',         [PostController::class, 'destroy']);
    Route::post('/posts/{id}/like',      [PostController::class, 'toggleLike']);
    Route::post('/posts/{id}/comment',   [PostController::class, 'addComment']);

    // --- Messaging ---
    Route::get('/messages',       [MessageController::class, 'index']);
    Route::post('/messages',      [MessageController::class, 'store']);
    Route::get('/conversations',  [MessageController::class, 'conversations']);

    // --- Gallery & Album Management ---
    Route::post('/gallery',  [GalleryController::class, 'storeGallery']);
    Route::post('/albums',   [GalleryController::class, 'storeAlbum']);

    // --- Image Management ---
    Route::post('/images/upload',   [ImageUploadController::class, 'upload']);
    Route::post('/images/archive',  [ImageUploadController::class, 'archive']);

    // --- Bookings & Payments ---
    Route::get('/bookings',                      [BookingController::class, 'index']);
    Route::get('/bookings/{id}',                 [BookingController::class, 'show']);
    Route::post('/bookings',                     [BookingController::class, 'store']);
    Route::patch('/bookings/{id}/status',        [BookingController::class, 'updateStatus']);
    Route::post('/bookings/{id}/pay',            [BookingController::class, 'pay']);
    Route::post('/bookings/{id}/confirm',        [BookingController::class, 'confirmPayment']);
    Route::post('/bookings/intent',              [BookingController::class, 'createIntent']);

    /*
    |--------------------------------------------------------------------------
    | Admin Routes — Require auth:sanctum + role.admin middleware
    |--------------------------------------------------------------------------
    */
    Route::middleware('role.admin')->group(function () {
        Route::get('/admin-stats', [AdminController::class, 'stats']);
    });
});
