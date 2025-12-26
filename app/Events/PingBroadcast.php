<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PingBroadcast.
 *
 * Simple test for broadcasting.
 */
final class PingBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param string $msg
     */
    public function __construct(public readonly string $msg) {}

    /**
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('test');
    }

    /**
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'Ping';
    }

    /**
     * @return string[]
     */
    public function broadcastWith(): array
    {
        return ['msg' => $this->msg];
    }
}
