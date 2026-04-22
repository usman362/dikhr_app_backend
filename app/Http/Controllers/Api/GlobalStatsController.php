<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\DhikrContribution;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class GlobalStatsController extends Controller
{
    /**
     * Return global dhikr statistics for the real-time dashboard.
     *
     * Cached for 30 seconds to reduce heavy aggregate queries.
     * Cache is busted automatically when new contributions arrive.
     */
    public function index(): JsonResponse
    {
        $data = Cache::remember('global_stats', 30, function () {
            $globalTotal = (int) DhikrContribution::query()->sum('count');

            $todayTotal = (int) DhikrContribution::query()
                ->whereDate('created_at', Carbon::today())
                ->sum('count');

            $thisWeekTotal = (int) DhikrContribution::query()
                ->where('created_at', '>=', Carbon::now()->startOfWeek())
                ->sum('count');

            $activeUsersToday = (int) DhikrContribution::query()
                ->whereDate('created_at', Carbon::today())
                ->distinct('user_id')
                ->count('user_id');

            $totalUsers = User::query()->count();

            $activeCampaigns = Campaign::query()->where('status', 'active')->count();
            $completedCampaigns = Campaign::query()->where('status', 'ended')->count();

            $topCampaigns = Campaign::query()
                ->where('status', 'active')
                ->withSum('contributions as current_total', 'count')
                ->with('creator:id,name')
                ->orderByDesc('current_total')
                ->take(5)
                ->get()
                ->map(fn ($c) => [
                    'id'            => $c->id,
                    'title'         => $c->title,
                    'current_total' => (int) ($c->current_total ?? 0),
                    'target_count'  => $c->target_count,
                    'progress'      => $c->target_count > 0
                        ? round(($c->current_total ?? 0) / $c->target_count * 100, 1)
                        : 0,
                    'creator'       => $c->creator?->name,
                ]);

            $recentActivity = DhikrContribution::query()
                ->with('user:id,name', 'campaign:id,title')
                ->orderByDesc('created_at')
                ->take(10)
                ->get()
                ->map(fn ($c) => [
                    'user_name'      => $c->user?->name,
                    'campaign_title' => $c->campaign?->title,
                    'count'          => $c->count,
                    'created_at'     => $c->created_at->toIso8601String(),
                ]);

            return [
                'global_total'        => $globalTotal,
                'today_total'         => $todayTotal,
                'this_week_total'     => $thisWeekTotal,
                'active_users_today'  => $activeUsersToday,
                'total_users'         => $totalUsers,
                'active_campaigns'    => $activeCampaigns,
                'completed_campaigns' => $completedCampaigns,
                'top_campaigns'       => $topCampaigns,
                'recent_activity'     => $recentActivity,
            ];
        });

        return response()->json($data);
    }
}
