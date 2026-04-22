<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DhikrContribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserStatsController extends Controller
{
    /**
     * Personal statistics for the authenticated user.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $totalCount = DhikrContribution::where('user_id', $userId)->sum('count');

        $todayCount = DhikrContribution::where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->sum('count');

        $weekCount = DhikrContribution::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->sum('count');

        $monthCount = DhikrContribution::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('count');

        $campaignsParticipated = DhikrContribution::where('user_id', $userId)
            ->distinct('campaign_id')
            ->count('campaign_id');

        // Calculate streak (consecutive days with contributions)
        $streak = $this->calculateStreak($userId);

        return response()->json([
            'total_count'            => (int) $totalCount,
            'today_count'            => (int) $todayCount,
            'this_week_count'        => (int) $weekCount,
            'this_month_count'       => (int) $monthCount,
            'campaigns_participated' => $campaignsParticipated,
            'current_streak'         => $streak,
        ]);
    }

    /**
     * Paginated contribution history for the authenticated user.
     */
    public function contributions(Request $request): JsonResponse
    {
        $contributions = DhikrContribution::where('user_id', $request->user()->id)
            ->with('campaign:id,title,status,target_count')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($contributions);
    }

    /**
     * Calculate consecutive-day streak for the given user.
     */
    private function calculateStreak(int $userId): int
    {
        $dates = DhikrContribution::where('user_id', $userId)
            ->selectRaw('DATE(created_at) as contribution_date')
            ->groupBy('contribution_date')
            ->orderByDesc('contribution_date')
            ->limit(365)
            ->pluck('contribution_date');

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $expected = Carbon::today();

        // If the user hasn't contributed today, start from yesterday
        if ($dates->first() !== $expected->toDateString()) {
            $expected = Carbon::yesterday();
        }

        foreach ($dates as $date) {
            if ($date === $expected->toDateString()) {
                $streak++;
                $expected->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}
