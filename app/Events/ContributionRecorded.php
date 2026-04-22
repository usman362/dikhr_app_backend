<?php

namespace App\Events;

use App\Models\DhikrContribution;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a user records a dhikr contribution.
 *
 * Frontend clients listen on "campaign.{id}" to update live counters.
 */
class ContributionRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly DhikrContribution $contribution,
        public readonly int $currentTotal,
        public readonly int $targetCount,
    ) {}

    /**
     * Public channel so any authenticated client watching this campaign
     * can receive the update without per-user authorization.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('campaign.' . $this->contribution->campaign_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'contribution.recorded';
    }

    public function broadcastWith(): array
    {
        return [
            'contribution_id' => $this->contribution->id,
            'campaign_id'     => $this->contribution->campaign_id,
            'user_id'         => $this->contribution->user_id,
            'count'           => $this->contribution->count,
            'current_total'   => $this->currentTotal,
            'target_count'    => $this->targetCount,
        ];
    }
}
