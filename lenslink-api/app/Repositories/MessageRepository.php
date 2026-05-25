<?php

namespace App\Repositories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class MessageRepository
{
    /**
     * Get message history for a gallery thread.
     */
    public function getByGallery(int $galleryId): Collection
    {
        return Message::with('sender:id,name,role_id')
            ->where('gallery_id', $galleryId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get direct message history between two users.
     */
    public function getDirectHistory(int $authId, int $receiverId): Collection
    {
        return Message::with('sender:id,name,role_id')
            ->where(function ($q) use ($authId, $receiverId) {
                $q->where('sender_id', $authId)->where('receiver_id', $receiverId)
                  ->orWhere('sender_id', $receiverId)->where('receiver_id', $authId);
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Create a new message.
     */
    public function create(array $data): Message
    {
        return Message::create($data)->load('sender:id,name,role_id');
    }

    /**
     * Mark gallery messages as read (for a given user).
     */
    public function markGalleryAsRead(int $userId, int $galleryId): void
    {
        Message::where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->where('gallery_id', $galleryId)
            ->update(['read_at' => now()]);
    }

    /**
     * Mark direct messages as read from a sender.
     */
    public function markDirectAsRead(int $receiverId, int $senderId): void
    {
        Message::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get all user IDs that the given user has exchanged messages with.
     */
    public function getContactIds(int $userId): SupportCollection
    {
        $sentTo      = Message::where('sender_id', $userId)->whereNotNull('receiver_id')->pluck('receiver_id');
        $receivedFrom = Message::where('receiver_id', $userId)->pluck('sender_id');

        return $sentTo->merge($receivedFrom)->unique();
    }

    /**
     * Get the last direct message between two users.
     */
    public function getLastDirectMessage(int $userId, int $contactId): ?Message
    {
        return Message::where(function ($q) use ($userId, $contactId) {
            $q->where('sender_id', $userId)->where('receiver_id', $contactId)
              ->orWhere('sender_id', $contactId)->where('receiver_id', $userId);
        })->latest()->first();
    }

    /**
     * Count unread messages from a sender to a receiver.
     */
    public function countUnread(int $senderId, int $receiverId): int
    {
        return Message::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->whereNull('read_at')
            ->count();
    }
}
