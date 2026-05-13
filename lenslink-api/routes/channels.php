<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channel authorization: verify the authenticated user is either
| the photographer who owns the gallery, or the client assigned to it.
|
*/

Broadcast::channel('chat.{galleryId}', function ($user, $galleryId) {
    $gallery = \App\Models\Gallery::find($galleryId);

    if (! $gallery) {
        return false;
    }

    // Photographer who owns the gallery
    if ($user->role_id == 2 && $gallery->photographer_id == $user->id) {
        return true;
    }

    // Client assigned to the gallery
    if ($user->role_id == 3 && $gallery->client_id == $user->id) {
        return true;
    }

    // Admin can listen to any chat
    if ($user->role_id == 1) {
        return true;
    }

    return false;
});
