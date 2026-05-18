<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Gallery;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;

class MessageService
{
    /**
     * Fetch message history between users or for a gallery.
     */
    public function getHistory(User $user, ?int $galleryId, ?int $receiverId): Collection
    {
        $query = Message::with('sender:id,name,role_id');

        if ($galleryId) {
            $this->authorizeGalleryAccess($user, $galleryId);
            $query->where('gallery_id', $galleryId);
        } else {
            $authId = $user->id;
            $query->where(function($q) use ($authId, $receiverId) {
                $q->where('sender_id', $authId)->where('receiver_id', $receiverId)
                  ->orWhere('sender_id', $receiverId)->where('receiver_id', $authId);
            });
        }

        $messages = $query->orderBy('created_at', 'asc')->get();

        // Mark messages as read
        $this->markAsRead($user, $galleryId, $receiverId);

        return $messages;
    }

    /**
     * Send a new message.
     */
    public function sendMessage(User $sender, array $data): Message
    {
        if (isset($data['gallery_id']) && $data['gallery_id']) {
            $this->authorizeGalleryAccess($sender, $data['gallery_id']);
        }

        $message = Message::create([
            'gallery_id'  => $data['gallery_id'] ?? null,
            'receiver_id' => $data['receiver_id'] ?? null,
            'sender_id'   => $sender->id,
            'body'        => $data['body'],
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return $message->load('sender:id,name,role_id');
    }

    /**
     * Get list of conversations for a user.
     */
    public function getConversations(User $user): Collection
    {
        $userId = $user->id;

        $sentTo = Message::where('sender_id', $userId)
            ->whereNotNull('receiver_id')
            ->pluck('receiver_id');
            
        $receivedFrom = Message::where('receiver_id', $userId)
            ->pluck('sender_id');

        $contactIds = $sentTo->merge($receivedFrom)->unique();

        return User::whereIn('id', $contactIds)
            ->select('id', 'name', 'avatar', 'specialty')
            ->get()
            ->map(function($contact) use ($userId) {
                $lastMsg = Message::where(function($q) use ($userId, $contact) {
                    $q->where('sender_id', $userId)->where('receiver_id', $contact->id)
                      ->orWhere('sender_id', $contact->id)->where('receiver_id', $userId);
                })->latest()->first();

                $contact->last_message = $lastMsg ? $lastMsg->body : '';
                $contact->last_message_at = $lastMsg ? $lastMsg->created_at : null;
                $contact->unread_count = Message::where('sender_id', $contact->id)
                    ->where('receiver_id', $userId)
                    ->whereNull('read_at')
                    ->count();

                return $contact;
            })->sortByDesc('last_message_at')->values();
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(User $user, ?int $galleryId, ?int $receiverId): void
    {
        $readUpdate = Message::where('sender_id', '!=', $user->id)
            ->whereNull('read_at');
        
        if ($galleryId) {
            $readUpdate->where('gallery_id', $galleryId);
        } else {
            $readUpdate->where('sender_id', $receiverId)->where('receiver_id', $user->id);
        }
        
        $readUpdate->update(['read_at' => now()]);
    }

    /**
     * Check if user owns the gallery (for photographers).
     */
    private function authorizeGalleryAccess(User $user, int $galleryId): void
    {
        $gallery = Gallery::findOrFail($galleryId);
        if ($user->role_id != 1 && $gallery->photographer_id !== $user->id && $gallery->client_id !== $user->id) {
            abort(403, 'Unauthorized access to this gallery.');
        }
    }
}
