<?php

declare(strict_types=1);

namespace App\Filament\Resources\Expenses\Schemas;

use App\Enums\ExpenseRecurrence;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(150),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),
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
            ]);
    }
}
