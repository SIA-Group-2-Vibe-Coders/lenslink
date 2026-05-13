<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Gallery;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * GET /messages?gallery_id=X
     * Fetch paginated message history for a gallery.
     * Accessible by the gallery's photographer or assigned client.
     */
    public function index(Request $request)
    {
        $request->validate([
            'gallery_id' => 'required|exists:galleries,id',
        ]);

        // Verify the authenticated user belongs to this gallery conversation
        $this->authorizeGalleryAccess($request->user(), $request->gallery_id);

        $messages = Message::where('gallery_id', $request->gallery_id)
            ->with('sender:id,name,role_id')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark all unread messages as read for the current user
        Message::where('gallery_id', $request->gallery_id)
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'data'   => $messages,
        ]);
    }

    /**
     * POST /messages
     * Send a new message and broadcast it via Pusher.
     */
    public function store(Request $request)
    {
        $request->validate([
            'gallery_id' => 'required|exists:galleries,id',
            'body'       => 'required|string|max:2000',
        ]);

        // Verify the authenticated user belongs to this gallery conversation
        $this->authorizeGalleryAccess($request->user(), $request->gallery_id);

        $message = Message::create([
            'gallery_id' => $request->gallery_id,
            'sender_id'  => $request->user()->id,
            'body'       => $request->body,
        ]);

        // Broadcast to private Pusher channel: chat.{gallery_id}
        broadcast(new MessageSent($message))->toOthers();

        $message->load('sender:id,name,role_id');

        return response()->json([
            'status'  => 'success',
            'message' => 'Message sent.',
            'data'    => $message,
        ], 201);
    }

    /**
     * Ensure the requesting user is the photographer who owns the gallery
     * or a client with access to it.
     * For now: photographer (role 2) can access any gallery; client (role 3) can access all.
     * Extend this once gallery ↔ client assignments are implemented.
     */
    private function authorizeGalleryAccess($user, $galleryId): void
    {
        $gallery = Gallery::findOrFail($galleryId);

        // Photographer can only chat in their own galleries
        if ($user->role_id == 2 && $gallery->photographer_id !== $user->id) {
            abort(403, 'You do not own this gallery.');
        }

        // Clients can access any public gallery chat for now
        // (Restrict further when client-gallery assignments are added)
    }
}
