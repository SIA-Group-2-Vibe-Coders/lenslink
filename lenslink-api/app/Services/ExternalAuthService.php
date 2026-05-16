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
            $verifiedIdToken = $auth->verifyIdToken($idToken);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
            $firebaseUser = $auth->getUser($firebaseUid);
        } catch (\Throwable $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_token' => ['The provided Firebase token is invalid or expired.']
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => $firebaseUser->email],
            [
                'name' => $firebaseUser->displayName ?? 'Firebase User',
                'password' => Hash::make(Str::random(24)), // Random password for social users
                'avatar' => $firebaseUser->photoUrl,
            ]
        );

        return $user;
    }
}
