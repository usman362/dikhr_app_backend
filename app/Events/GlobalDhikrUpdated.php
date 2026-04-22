<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when the global dhikr total changes.
 *
 * Frontend clients listen on "dhikr.global" to update the global counter.
 */
class GlobalDhikrUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $globalTotal,
        public readonly int $todayTotal,
        public readonly int $activeUsers,
        public readonly int $lastContributionUserId,
        public readonly string $lastContributionUserName,
        public readonly int $lastContributionCount,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('dhikr.global'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dhikr.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'global_total'   => $this->globalTotal,
            'today_total'    => $this->todayTotal,
            'active_users'   => $this->activeUsers,
            'last_contribution' => [
                'user_id'   => $this->lastContributionUserId,
                'user_name' => $this->lastContributionUserName,
                'count'     => $this->lastContributionCount,
            ],
        ];
    }
}
