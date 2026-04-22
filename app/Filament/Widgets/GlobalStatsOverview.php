<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Models\DhikrContribution;
use App\Models\Group;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class GlobalStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $globalTotal = number_format(DhikrContribution::query()->sum('count'));

        $todayTotal = number_format(DhikrContribution::query()
            ->whereDate('created_at', Carbon::today())
            ->sum('count'));

        $activeUsersToday = DhikrContribution::query()
            ->whereDate('created_at', Carbon::today())
            ->distinct('user_id')
            ->count('user_id');

        $totalUsers = User::query()->count();
        $totalGroups = Group::query()->count();

        $activeCampaigns = Campaign::query()
            ->where('status', 'active')
            ->count();

        // Weekly trend (last 7 days daily totals)
        $weeklyData = collect(range(6, 0))->map(function ($daysAgo) {
            return (int) DhikrContribution::query()
                ->whereDate('created_at', Carbon::today()->subDays($daysAgo))
                ->sum('count');
        })->toArray();

        return [
            Stat::make('Global Dhikr Total', $globalTotal)
                ->description('All-time across all users')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success')
                ->chart($weeklyData),

            Stat::make('Today\'s Dhikr', $todayTotal)
                ->description("{$activeUsersToday} active users today")
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Active Campaigns', (string) $activeCampaigns)
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-flag')
                ->color('warning'),

            Stat::make('Total Users', number_format($totalUsers))
                ->description("{$totalGroups} groups formed")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }
}
