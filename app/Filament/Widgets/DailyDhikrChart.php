<?php

namespace App\Filament\Widgets;

use App\Models\DhikrContribution;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DailyDhikrChart extends ChartWidget
{
    protected ?string $heading = 'Daily Dhikr (Last 30 Days)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            $total = (int) DhikrContribution::query()
                ->whereDate('created_at', $date)
                ->sum('count');

            return [
                'date'  => $date->format('M d'),
                'total' => $total,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Dhikr Count',
                    'data' => $days->pluck('total')->toArray(),
                    'borderColor' => '#0D6B3F',
                    'backgroundColor' => 'rgba(13, 107, 63, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $days->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
