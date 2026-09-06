<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignments\Schemas;

use App\Filament\Support\WizardSubmitActions;
use App\Models\StaffAssignment;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

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
                                ->required()
                                ->unique(
                                    table: StaffAssignment::class,
                                    column: 'subject_id',
                                    ignorable: fn (?StaffAssignment $record): ?StaffAssignment => $record,
                                    modifyRuleUsing: fn (Unique $rule, \Filament\Forms\Get $get): Unique => $rule
                                        ->where('student_class_id', (string) $get('student_class_id')),
                                )
                                ->validationMessages([
                                    'unique' => 'Another teacher is already assigned to teach this subject to this class. A class + subject pair can only have one teacher.',
                                ])
                                ->helperText('Each class + subject pair can only be assigned to one teacher.'),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
