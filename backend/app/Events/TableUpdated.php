<?php

namespace App\Events;

use App\Models\Ban;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ban;

    /**
     * Create a new event instance.
     */
    public function __construct(Ban $ban)
    {
        $this->ban = $ban;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('pos-orders'), // Dùng chung channel với orders để đơn giản
        ];
    }

    public function broadcastAs()
    {
        return 'table-updated';
    }
}
