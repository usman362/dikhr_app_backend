<?php

namespace App\Filament\Resources\DhikrContributions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DhikrContributionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('campaign_id')
                    ->relationship('campaign', 'title')
                    ->required(),
                TextInput::make('count')
                    ->required()
                    ->numeric(),
            ]);
    }
}
