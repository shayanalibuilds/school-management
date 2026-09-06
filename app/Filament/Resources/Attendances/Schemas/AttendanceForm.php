<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendances\Schemas;

use App\Enums\AttendanceStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Attendance')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->schema([
                            DatePicker::make('date')
                                ->required(),
                            Select::make('status')
                                ->options(collect(AttendanceStatus::cases())
                                    ->mapWithKeys(fn (AttendanceStatus $status): array => [$status->value => $status->label()])
                                    ->all())
                                ->required(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
