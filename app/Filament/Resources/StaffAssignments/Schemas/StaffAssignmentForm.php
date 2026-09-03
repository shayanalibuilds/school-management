<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

final class StaffAssignmentForm
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
                Select::make('student_class_id')
                    ->label('Class')
                    ->relationship('studentClass', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
