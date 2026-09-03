<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('staff_id')
                    ->label('Staff member')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('month')
                    ->label('Month (YYYY-MM)')
                    ->default(today()->format('Y-m'))
                    ->regex('/^\d{4}-\d{2}$/')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('PKR')
                    ->required(),
                DatePicker::make('paid_at')
                    ->label('Paid on')
                    ->helperText('Leave empty while payroll is pending.'),
            ]);
    }
}
