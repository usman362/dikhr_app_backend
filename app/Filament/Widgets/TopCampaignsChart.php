<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use Filament\Widgets\ChartWidget;

class TopCampaignsChart extends ChartWidget
{
    protected ?string $heading = 'Top Active Campaigns';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $campaigns = Campaign::query()
            ->where('status', 'active')
            ->withSum('contributions as current_total', 'count')
            ->orderByDesc('current_total')
            ->take(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Current Progress',
                    'data' => $campaigns->pluck('current_total')->map(fn ($v) => (int) ($v ?? 0))->toArray(),
                    'backgroundColor' => '#0D6B3F',
                ],
                [
                    'label' => 'Target',
                    'data' => $campaigns->pluck('target_count')->toArray(),
                    'backgroundColor' => '#D4A843',
                ],
            ],
            'labels' => $campaigns->pluck('title')->map(fn ($t) => strlen($t) > 25 ? substr($t, 0, 22) . '...' : $t)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
