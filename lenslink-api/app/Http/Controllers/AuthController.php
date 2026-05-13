<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
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
}

