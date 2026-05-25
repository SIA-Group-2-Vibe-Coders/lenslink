<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Traits\ApiResponse;
use App\Services\AuthService;
use App\Services\ExternalAuthService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService,
        protected ExternalAuthService $externalAuthService
    ) {}

    /**
     * POST /register
     */
    public function register(RegisterRequest $request)
    {
        $user  = $this->authService->register($request->validated());
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->createdResponse([
            'token' => $token,
            'user'  => $user,
        ], 'Registration successful');
    }

    /**
     * POST /login
     */
    public function login(LoginRequest $request)
    {
        $authData = $this->authService->login($request->validated());

        return $this->successResponse([
            'token' => $authData['token'],
            'user'  => $authData['user'],
        ], 'Login successful');
    }

    /**
     * GET /profile
     */
    public function profile(Request $request)
    {
        return $this->successResponse($request->user());
    }

    /**
     * POST /logout
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * POST /profile/update
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Handle Avatar Upload
        if ($request->hasFile('avatar')) {
            $uploadResult    = Cloudinary::uploadApi()->upload($request->file('avatar')->getRealPath(), [
                'folder'        => 'lenslink/avatars',
                'resource_type' => 'image',
            ]);
            $data['avatar'] = $uploadResult['secure_url'];
        }

        // Handle Cover Photo Upload
        if ($request->hasFile('cover_photo')) {
            $uploadResult       = Cloudinary::uploadApi()->upload($request->file('cover_photo')->getRealPath(), [
                'folder'        => 'lenslink/covers',
                'resource_type' => 'image',
            ]);
            $data['cover_photo'] = $uploadResult['secure_url'];
        }

        $user->update($data);

        return $this->successResponse($user->fresh(), 'Profile updated successfully');
    }

    /**
     * POST /auth/firebase
     * Sync and login with a Firebase ID token.
     */
    public function firebaseSync(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        $user  = $this->externalAuthService->authenticateWithFirebase($request->id_token);
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'token'        => $token,
            'access_token' => $token,
            'user'         => $user,
        ], 'Firebase authentication successful');
    }
}
