<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AuthController extends Controller
{
    protected $authService;
    protected $externalAuthService;

    public function __construct(AuthService $authService, \App\Services\ExternalAuthService $externalAuthService)
    {
        $this->authService = $authService;
        $this->externalAuthService = $externalAuthService;
    }

    /**
     * POST /register
     */
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|string|email|unique:users,email',
            'password'  => 'required|string|min:6',
            'role_id'   => 'required|integer|exists:roles,id'
        ]);

        $user = $this->authService->register($request->all());
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Registration successful',
            'token'   => $token,
            'data'    => $user
        ], 201);
    }

    /**
     * POST /login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $authData = $this->authService->login($request->only('email', 'password'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Login successful',
            'token'   => $authData['token'],
            'data'    => $authData['user']
        ]);
    }

    /**
     * GET /profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user()
        ]);
    }

    /**
     * POST /logout
     */
    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * POST /profile/update
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'specialty' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'price_range' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:5120',
            'cover_photo' => 'nullable|image|max:10240',
        ]);

        $data = $request->only('name', 'bio', 'specialty', 'location', 'price_range');

        // Handle Avatar Upload
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $uploadResult = Cloudinary::uploadApi()->upload($avatarFile->getRealPath(), [
                'folder' => 'lenslink/avatars',
                'resource_type' => 'image',
            ]);
            $data['avatar'] = $uploadResult['secure_url'];
        }

        // Handle Cover Photo Upload
        if ($request->hasFile('cover_photo')) {
            $coverFile = $request->file('cover_photo');
            $uploadResult = Cloudinary::uploadApi()->upload($coverFile->getRealPath(), [
                'folder' => 'lenslink/covers',
                'resource_type' => 'image',
            ]);
            $data['cover_photo'] = $uploadResult['secure_url'];
        }

        $user->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    /**
     * POST /api/auth/firebase
     * Sync and login with a Firebase token.
     */
    public function firebaseSync(Request $request)
    {
        $request->validate(['id_token' => 'required']);

        $user = $this->externalAuthService->authenticateWithFirebase($request->id_token);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'access_token' => $token,
            'user' => $user,
        ]);
    }
}

