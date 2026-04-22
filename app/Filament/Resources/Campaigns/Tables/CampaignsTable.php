<?php

namespace App\Filament\Resources\Campaigns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('scope')
                    ->label('Scope')
                    ->badge()
                    ->state(fn ($record) => $record->group_id === null ? 'Ummah (Global)' : 'Group')
                    ->color(fn (string $state): string => $state === 'Ummah (Global)' ? 'success' : 'warning'),

                TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'ended' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('contributions_sum_count')
                    ->label('Progress')
                    ->sum('contributions', 'count')
                    ->numeric()
                    ->formatStateUsing(function ($state, $record) {
                        $current = number_format((int) ($state ?? 0));
                        $target = number_format($record->target_count);
                        $pct = $record->target_count > 0
                            ? round(($state ?? 0) / $record->target_count * 100, 1)
                            : 0;
                        return "{$current} / {$target} ({$pct}%)";
                    })
                    ->sortable(),

                TextColumn::make('target_count')
                    ->label('Target')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('creator.name')
                    ->label('Creator')
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ends_at')
                    ->dateTime('M d, Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'ended' => 'Ended',
                    ]),
                Filter::make('global')
                    ->label('Ummah (Global) only')
                    ->query(fn (Builder $query): Builder => $query->whereNull('group_id'))
                    ->toggle(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
