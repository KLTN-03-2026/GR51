<?php

namespace App\Events;

use App\Models\DonHang;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $donHang;
    public string $nguon; // 'pos' | 'qr'

    /**
     * Create a new event instance.
     * @param DonHang $donHang
     * @param string $nguon 'pos' nếu nhân viên tự tạo, 'qr' nếu khách QR hoặc web
     */
    public function __construct(DonHang $donHang, string $nguon = 'qr')
    {
        $this->donHang = $donHang;
        $this->nguon = $nguon;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('pos-orders'),
        ];
    }

    public function broadcastAs()
    {
        return 'new-order';
    }
}
