<?php

namespace App\Services;

use Kreait\Laravel\Firebase\Facades\Firebase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ExternalAuthService
{
    /**
     * Verify a Firebase ID Token and sync with local user.
     */
    public function authenticateWithFirebase(string $idToken)
    {
        $auth = Firebase::auth();
        
        try {
            // Add a 120-second clock skew leeway to prevent token verification failure due to minor clock mismatches.
            $verifiedIdToken = $auth->verifyIdToken($idToken, false, 120);
            
            // Extract user claims directly from the verified ID token.
            // This avoids making a secondary getUser() API call, which saves a network roundtrip and bypasses private key validation issues.
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name') ?? 'Firebase User';
            $avatar = $verifiedIdToken->claims()->get('picture');
            
            if (!$email) {
                throw new \Exception("The ID token does not contain a valid email claim.");
            }
        } catch (\Throwable $e) {
            \Log::error('Firebase verification failed: ' . $e->getMessage());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_token' => ['The provided Firebase token is invalid or expired: ' . $e->getMessage()]
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'email'    => $email,
                'name'     => $name,
                'password' => Hash::make(Str::random(24)), // Random password for social users
                'avatar'   => $avatar,
                'role_id'  => 2,
            ]);
        } else {
            // Update name and avatar if not set, without touching password
            $user->update([
                'name'   => $user->name ?: $name,
                'avatar' => $user->avatar ?: $avatar,
            ]);
        }

        return $user;
    }
}
