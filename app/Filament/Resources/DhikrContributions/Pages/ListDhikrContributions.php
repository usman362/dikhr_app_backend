<?php

namespace App\Filament\Resources\DhikrContributions\Pages;

use App\Filament\Resources\DhikrContributions\DhikrContributionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDhikrContributions extends ListRecords
{
    protected static string $resource = DhikrContributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
