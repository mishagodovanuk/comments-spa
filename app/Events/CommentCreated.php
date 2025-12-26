<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $commentId,
        public readonly ?int $parentId = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('comments');
    }

    public function broadcastAs(): string
    {
        return 'CommentCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->commentId,
            'parent_id' => $this->parentId,
        ];
    }
}
