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

    // Owner or assigned client of the gallery
    if ($gallery->photographer_id == $user->id || $gallery->client_id == $user->id) {
        return true;
    }

    // Admin can listen to any chat
    if ($user->role_id == 1) {
        return true;
    }

    return false;
});

Broadcast::channel('chat.direct.{ids}', function ($user, $ids) {
    $allowedIds = explode('-', $ids);
    return in_array($user->id, $allowedIds);
});
