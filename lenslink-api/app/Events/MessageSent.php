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
        // Load sender so it's included in the broadcast payload
        $this->message->load('sender:id,name,role_id');
    }

    /**
     * The private channel name is gallery-specific.
     * Both the photographer and client subscribe to the same channel
     * for the gallery they share.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->gallery_id),
        ];
    }

    /**
     * Shape of the data sent to the frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'gallery_id' => $this->message->gallery_id,
            'body'       => $this->message->body,
            'sender'     => $this->message->sender,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
