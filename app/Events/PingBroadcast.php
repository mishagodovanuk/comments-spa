<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PingBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly string $msg) {}

    public function broadcastOn(): Channel
    {
        return new Channel('test');
    }

    public function broadcastAs(): string
    {
        return 'Ping';
    }

    public function broadcastWith(): array
    {
        return ['msg' => $this->msg];
    }
}
