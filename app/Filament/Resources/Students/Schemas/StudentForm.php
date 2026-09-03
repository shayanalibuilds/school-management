<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Schemas;

use App\Enums\StudentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sr_no')
                    ->label('SR #')
                    ->numeric()
                    ->unique(ignoreRecord: true)
                    ->helperText('Leave empty to assign the next sequential number.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('student_class_id')
                    ->label('Class')
                    ->relationship('studentClass', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('joining_date')
                    ->required(),
                DatePicker::make('leaving_date'),
                Select::make('status')
                    ->options(collect(StudentStatus::cases())
                        ->mapWithKeys(fn (StudentStatus $status): array => [$status->value => $status->label()])
                        ->all())
                    ->default(StudentStatus::Active->value)
                    ->required(),
            ]);
    }
}
