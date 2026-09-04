<?php

declare(strict_types=1);

namespace App\Filament\Resources\Expenses\Schemas;

use App\Enums\ExpenseRecurrence;
use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Details')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(150),
                            Textarea::make('description')
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Step::make('Amount & recurrence')
                        ->icon('heroicon-m-banknotes')
                        ->schema([
                            TextInput::make('amount')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('PKR')
                                ->required(),
                            Select::make('recurrence')
                                ->options(collect(ExpenseRecurrence::cases())
                                    ->mapWithKeys(fn (ExpenseRecurrence $recurrence): array => [$recurrence->value => $recurrence->label()])
                                    ->all())
                                ->default(ExpenseRecurrence::OneTime->value)
                                ->required(),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
