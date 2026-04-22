<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('group_id')
                    ->label('Group')
                    ->placeholder('— Global (Ummah) Campaign —')
                    ->helperText('Leave empty to create a GLOBAL campaign visible to every user in the app. Select a group to restrict this campaign to group members only.')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->helperText('E.g. "Ramadan 2026 Dhikr Drive" or "Jumu\'ah Salawat Challenge"'),
                Select::make('dhikr')
                    ->label('Dhikr')
                    ->helperText('The specific dhikr users will count for this campaign. The counter screen locks to this dhikr so everyone contributes the same zikr. Leave empty if the campaign is not tied to a single dhikr.')
                    ->options([
                        'SubhanAllah' => 'SubhanAllah (سُبْحَانَ اللَّهِ) — Glory be to Allah',
                        'Alhamdulillah' => 'Alhamdulillah (الْحَمْدُ لِلَّهِ) — All praise is due to Allah',
                        'Allahu Akbar' => 'Allahu Akbar (اللَّهُ أَكْبَرُ) — Allah is the Greatest',
                        'La ilaha illAllah' => 'La ilaha illAllah (لَا إِلَهَ إِلَّا اللَّهُ) — No god but Allah',
                        'Astaghfirullah' => 'Astaghfirullah (أَسْتَغْفِرُ اللَّهَ) — I seek forgiveness',
                    ])
                    ->searchable()
                    ->nullable(),
                Textarea::make('description')
                    ->helperText('Explain the purpose and any dhikr-specific guidance. Shown on the campaign detail screen.')
                    ->columnSpanFull(),
                TextInput::make('target_count')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Total dhikr count the Ummah should reach together.'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft (hidden from users)',
                        'active' => 'Active (visible in app)',
                        'ended' => 'Ended (read-only)',
                    ])
                    ->helperText('Only "Active" campaigns appear in the app. Keep as Draft while editing.')
                    ->required()
                    ->default('draft'),
            ]);
    }
}
