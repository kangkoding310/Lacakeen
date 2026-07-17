<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Conversation $conversation, public readonly User $recipient) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("App.Models.User.{$this->recipient->id}")];
    }

    public function broadcastAs(): string
    {
        return 'ConversationCreated';
    }

    public function broadcastWith(): array
    {
        $this->conversation->loadMissing('participants:id,name,avatar');

        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'type' => $this->conversation->type,
                'name' => $this->conversation->name,
                'participants' => $this->conversation->participants,
            ],
        ];
    }
}
