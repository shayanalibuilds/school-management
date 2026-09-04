<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Schemas;

use App\Enums\FeeStructureType;
use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class FeeStructureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Fee head')
                        ->icon('heroicon-m-list-bullet')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(150),
                            Select::make('type')
                                ->options(collect(FeeStructureType::cases())
                                    ->mapWithKeys(fn (FeeStructureType $type): array => [$type->value => $type->label()])
                                    ->all())
                                ->default(FeeStructureType::Monthly->value)
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Amount')
                        ->icon('heroicon-m-banknotes')
                        ->schema([
                            TextInput::make('amount')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('PKR')
                                ->required(),
                            WizardSubmitActions::make(),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
