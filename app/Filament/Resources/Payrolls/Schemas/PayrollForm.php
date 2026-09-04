<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Schemas;

use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Pay period')
                        ->icon('heroicon-m-calendar-days')
                        ->schema([
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
                        ])
                        ->columns(2),
                    Step::make('Salary')
                        ->icon('heroicon-m-banknotes')
                        ->schema([
                            TextInput::make('amount')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('PKR')
                                ->required(),
                            DatePicker::make('paid_at')
                                ->label('Paid on')
                                ->helperText('Leave empty while payroll is pending.'),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
