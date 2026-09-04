<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses\Schemas;

use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class StudentClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Class details')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            TextInput::make('name')
                                ->label('Class name')
                                ->required()
                                ->maxLength(100)
                                ->unique(ignoreRecord: true),
                            WizardSubmitActions::make(),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
