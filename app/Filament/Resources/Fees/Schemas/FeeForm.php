<?php

declare(strict_types=1);

namespace App\Filament\Resources\Fees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class FeeForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentYear = (int) today()->year;

        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('fee_structure_id')
                    ->label('Fee structure')
                    ->relationship('feeStructure', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('year')
                    ->options(collect(range($currentYear - 9, $currentYear + 1))
                        ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                        ->all())
                    ->default((string) $currentYear)
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('PKR')
                    ->required()
                    ->helperText('Pre-filled from the fee structure when the page loads; adjust if needed.'),
                DatePicker::make('due_date'),
            ]);
    }
}
