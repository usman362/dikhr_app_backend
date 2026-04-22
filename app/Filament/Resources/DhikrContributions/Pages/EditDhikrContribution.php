<?php

namespace App\Filament\Resources\DhikrContributions\Pages;

use App\Filament\Resources\DhikrContributions\DhikrContributionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDhikrContribution extends EditRecord
{
    protected static string $resource = DhikrContributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
