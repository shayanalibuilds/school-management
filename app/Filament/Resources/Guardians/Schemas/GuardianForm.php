<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Schemas;

use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class GuardianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Personal details')
                        ->icon('heroicon-m-user')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('cnic')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->placeholder('35202-1234567-1')
                                ->maxLength(15)
                                ->helperText('Guardians can also use this CNIC on the public pages.'),
                        ])
                        ->columns(2),
                    Step::make('Contact')
                        ->icon('heroicon-m-phone')
                        ->schema([
                            TextInput::make('phone')
                                ->tel()
                                ->required()
                                ->maxLength(20),
                            TextInput::make('relation')
                                ->label('Relation to student')
                                ->maxLength(255),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
