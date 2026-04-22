<?php

namespace App\Http\Controllers\Api;

use App\Events\ContributionRecorded;
use App\Events\GlobalDhikrUpdated;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\DhikrContribution;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DhikrContributionController extends Controller
{
    /**
     * Store a new dhikr contribution against an active campaign.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'count'       => ['required', 'integer', 'min:1', 'max:99999'],
        ]);

        $campaign = Campaign::query()->findOrFail($data['campaign_id']);

        if ($campaign->status !== 'active') {
            abort(422, 'Campaign is not active.');
        }

        if ($campaign->group_id !== null) {
            $group = Group::query()->findOrFail($campaign->group_id);
            if (! $group->hasMember($request->user())) {
                abort(403, 'You must join the group to contribute.');
            }
        }

        $contribution = DhikrContribution::query()->create([
            'user_id'     => $request->user()->id,
            'campaign_id' => $campaign->id,
            'count'       => $data['count'],
        ]);

        $currentTotal = (int) $campaign->contributions()->sum('count');

        // Bust global stats cache
        Cache::forget('global_stats');

        // Broadcast campaign-specific update
        ContributionRecorded::dispatch($contribution, $currentTotal, $campaign->target_count);

        // Broadcast global dhikr counter update
        $globalTotal = (int) DhikrContribution::query()->sum('count');
        $todayTotal  = (int) DhikrContribution::query()
            ->whereDate('created_at', Carbon::today())
            ->sum('count');
        $activeUsers = (int) DhikrContribution::query()
            ->whereDate('created_at', Carbon::today())
            ->distinct('user_id')
            ->count('user_id');

        GlobalDhikrUpdated::dispatch(
            $globalTotal,
            $todayTotal,
            $activeUsers,
            $request->user()->id,
            $request->user()->name,
            $data['count'],
        );

        return response()->json([
            'contribution'  => $contribution,
            'campaign_id'   => $campaign->id,
            'current_total' => $currentTotal,
            'target_count'  => $campaign->target_count,
            'global_total'  => $globalTotal,
        ], 201);
    }
}
