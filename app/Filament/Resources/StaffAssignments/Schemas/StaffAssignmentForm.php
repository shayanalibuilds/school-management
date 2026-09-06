<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignments\Schemas;

use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class StaffAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Staff member')
                        ->icon('heroicon-m-user')
                        ->schema([
                            Select::make('staff_id')
                                ->label('Staff member')
                                ->relationship('staff', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                    Step::make('Class & subject')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
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
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
