<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Gallery;
use App\Models\User;
use App\Repositories\MessageRepository;
use Illuminate\Support\Collection;

class MessageService
{
    public function __construct(protected MessageRepository $messageRepository) {}

    /**
     * Fetch message history between users or for a gallery.
     */
    public function getHistory(User $user, ?int $galleryId, ?int $receiverId): Collection
    {
        if ($galleryId) {
            $this->authorizeGalleryAccess($user, $galleryId);
            $messages = $this->messageRepository->getByGallery($galleryId);
            $this->messageRepository->markGalleryAsRead($user->id, $galleryId);
        } else {
            $messages = $this->messageRepository->getDirectHistory($user->id, $receiverId);
            $this->messageRepository->markDirectAsRead($user->id, $receiverId);
        }

        return $messages;
    }

    /**
     * Send a new message.
     */
    public function sendMessage(User $sender, array $data): \App\Models\Message
    {
        if (!empty($data['gallery_id'])) {
            $this->authorizeGalleryAccess($sender, (int) $data['gallery_id']);
        }

        $message = $this->messageRepository->create([
            'gallery_id'  => $data['gallery_id'] ?? null,
            'receiver_id' => $data['receiver_id'] ?? null,
            'sender_id'   => $sender->id,
            'body'        => $data['body'],
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    /**
     * Get list of conversations (contacts) for a user.
     */
    public function getConversations(User $user): Collection
    {
        $contactIds = $this->messageRepository->getContactIds($user->id);

        return User::whereIn('id', $contactIds)
            ->select('id', 'name', 'avatar', 'specialty')
            ->get()
            ->map(function ($contact) use ($user) {
                $lastMsg = $this->messageRepository->getLastDirectMessage($user->id, $contact->id);

                $contact->last_message    = $lastMsg?->body ?? '';
                $contact->last_message_at = $lastMsg?->created_at;
                $contact->unread_count    = $this->messageRepository->countUnread($contact->id, $user->id);

                return $contact;
            })
            ->sortByDesc('last_message_at')
            ->values();
    }

    /**
     * Check if user is authorized to access a gallery's messages.
     */
    private function authorizeGalleryAccess(User $user, int $galleryId): void
    {
        $gallery = Gallery::findOrFail($galleryId);

        if (
            $user->role_id != 1 &&
            $gallery->photographer_id !== $user->id &&
            $gallery->client_id !== $user->id
        ) {
            abort(403, 'Unauthorized access to this gallery.');
        }
    }
}
