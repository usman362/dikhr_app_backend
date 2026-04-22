<?php

namespace App\Filament\Resources\DhikrContributions;

use App\Filament\Resources\DhikrContributions\Pages\CreateDhikrContribution;
use App\Filament\Resources\DhikrContributions\Pages\EditDhikrContribution;
use App\Filament\Resources\DhikrContributions\Pages\ListDhikrContributions;
use App\Filament\Resources\DhikrContributions\Schemas\DhikrContributionForm;
use App\Filament\Resources\DhikrContributions\Tables\DhikrContributionsTable;
use App\Models\DhikrContribution;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DhikrContributionResource extends Resource
{
    protected static ?string $model = DhikrContribution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DhikrContributionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DhikrContributionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDhikrContributions::route('/'),
            'create' => CreateDhikrContribution::route('/create'),
            'edit' => EditDhikrContribution::route('/{record}/edit'),
        ];
    }
}
