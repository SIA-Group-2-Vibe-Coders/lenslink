<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->load('sender:id,name,role_id');
    }

    /**
     * Determine the channel based on whether it's a gallery chat or direct chat.
     */
    public function broadcastOn(): array
    {
        if ($this->message->gallery_id) {
            return [new PrivateChannel('chat.' . $this->message->gallery_id)];
        }

        // For direct chat, use a deterministic channel name: direct.{minId}-{maxId}
        $ids = [$this->message->sender_id, $this->message->receiver_id];
        sort($ids);
        return [new PrivateChannel('chat.direct.' . $ids[0] . '-' . $ids[1])];
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->message->id,
            'gallery_id'  => $this->message->gallery_id,
            'receiver_id' => $this->message->receiver_id,
            'body'        => $this->message->body,
            'sender'      => $this->message->sender,
            'created_at'  => $this->message->created_at->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
