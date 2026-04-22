<?php

namespace App\Filament\Widgets;

use App\Models\DhikrContribution;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Contributions';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DhikrContribution::query()
                    ->with('user:id,name', 'campaign:id,title')
                    ->orderByDesc('created_at')
                    ->limit(15)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),

                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('count')
                    ->label('Count')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),
            ])
            ->paginated([10, 15, 25]);
    }
}
