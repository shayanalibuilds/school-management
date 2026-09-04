<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Schemas;

use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Subject details')
                        ->icon('heroicon-m-book-open')
                        ->schema([
                            TextInput::make('name')
                                ->label('Subject name')
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
