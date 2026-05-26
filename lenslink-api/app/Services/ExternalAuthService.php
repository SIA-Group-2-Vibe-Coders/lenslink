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

        $user = User::where('email', $firebaseUser->email)->first();

        if (!$user) {
            $user = User::create([
                'email'    => $firebaseUser->email,
                'name'     => $firebaseUser->displayName ?? 'Firebase User',
                'password' => Hash::make(Str::random(24)), // Random password for social users
                'avatar'   => $firebaseUser->photoUrl,
                'role_id'  => 2,
            ]);
        } else {
            // Update name and avatar if not set, without touching password
            $user->update([
                'name'   => $user->name ?: ($firebaseUser->displayName ?? 'Firebase User'),
                'avatar' => $user->avatar ?: $firebaseUser->photoUrl,
            ]);
        }

        return $user;
    }
}
