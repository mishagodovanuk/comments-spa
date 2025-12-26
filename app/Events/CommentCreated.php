<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * CommentCreated.
 *
 * Simple pusher.
 */
final class CommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $commentId
     * @param int|null $parentId
     */
    public function __construct(
        public readonly int $commentId,
        public readonly ?int $parentId = null,
    ) {}

    /**
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('comments');
    }

    /**
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'CommentCreated';
    }

    /**
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->commentId,
            'parent_id' => $this->parentId,
        ];
    }
}
