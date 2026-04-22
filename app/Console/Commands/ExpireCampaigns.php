<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireCampaigns extends Command
{
    protected $signature = 'campaigns:expire';

    protected $description = 'Auto-expire active campaigns that have passed their end date or reached their target';

    public function handle(): int
    {
        // End campaigns past their end date
        $expiredByDate = Campaign::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', Carbon::now())
            ->get();

        foreach ($expiredByDate as $campaign) {
            $campaign->update(['status' => 'ended']);
            $this->info("Expired campaign #{$campaign->id}: {$campaign->title} (past end date)");
        }

        // End campaigns that reached their target
        $activeCampaigns = Campaign::query()
            ->where('status', 'active')
            ->withSum('contributions as current_total', 'count')
            ->get();

        $expiredByTarget = 0;
        foreach ($activeCampaigns as $campaign) {
            $total = (int) ($campaign->current_total ?? 0);
            if ($total >= $campaign->target_count) {
                $campaign->update(['status' => 'ended']);
                $expiredByTarget++;
                $this->info("Completed campaign #{$campaign->id}: {$campaign->title} (target reached: {$total}/{$campaign->target_count})");
            }
        }

        $totalExpired = $expiredByDate->count() + $expiredByTarget;
        $this->info("Total campaigns expired: {$totalExpired}");

        return self::SUCCESS;
    }
}
