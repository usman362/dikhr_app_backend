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
     *
     * Hot path: this fires every time a member taps the counter and
     * commits a batch. We keep the synchronous work minimal — write
     * the contribution, recompute the campaign total (one indexed
     * query), bust the global stats cache, and dispatch broadcast
     * events. The events fan out to WebSocket subscribers and let
     * connected clients refresh their own totals from cached values
     * instead of recomputing here.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            // Cap at 99,999 per write — anything higher is almost
            // certainly a client bug or someone fuzzing the API. Real
            // taps come in batches of 1-100.
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

        // Campaign total — one indexed sum, fast.
        $currentTotal = (int) $campaign->contributions()->sum('count');

        // Bust global stats cache so the next /global-stats fetch sees
        // the new contribution. The actual recompute happens lazily on
        // the next reader, not here on the hot path.
        Cache::forget('global_stats');

        // Broadcast campaign-specific update to listeners on that
        // campaign's WebSocket channel.
        ContributionRecorded::dispatch($contribution, $currentTotal, $campaign->target_count);

        // Global counter — single sum. Today / active-users live in
        // the cached global_stats payload, so we don't recompute them
        // here (they were eating ~3 extra queries per tap before).
        $globalTotal = (int) DhikrContribution::query()->sum('count');

        GlobalDhikrUpdated::dispatch(
            $globalTotal,
            // todayTotal / activeUsers are sourced from the cached
            // global stats on the consumer side — sending 0 here as a
            // placeholder is fine because clients reconcile via the
            // global-stats fetch triggered by the same event.
            0,
            0,
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
