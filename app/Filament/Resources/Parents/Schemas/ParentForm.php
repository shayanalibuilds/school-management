<?php

declare(strict_types=1);

namespace App\Filament\Resources\Parents\Schemas;

use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class ParentForm
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
                                ->helperText('Parents use this CNIC on the public pages to look up their children.'),
                        ])
                        ->columns(2),
                    Step::make('Contact')
                        ->icon('heroicon-m-phone')
                        ->schema([
                            TextInput::make('phone')
                                ->tel()
                                ->required()
                                ->maxLength(20),
                            TextInput::make('occupation')
                                ->maxLength(255),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
