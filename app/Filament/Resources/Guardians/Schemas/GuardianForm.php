<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Schemas;

use App\Filament\Support\WizardSubmitActions;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class GuardianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Personal details')
                        ->icon('heroicon-m-user')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('cnic')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->placeholder('35202-1234567-1')
                                ->maxLength(15)
                                ->helperText('Guardians can also use this CNIC on the public pages.'),
                        ])
                        ->columns(2),
                    Step::make('Contact')
                        ->icon('heroicon-m-phone')
                        ->schema([
                            TextInput::make('phone')
                                ->tel()
                                ->required()
                                ->maxLength(20),
                            TextInput::make('relation')
                                ->label('Relation to student')
                                ->maxLength(255),
                        ])
                        ->columns(2),
                    Step::make('Students')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            Select::make('students')
                                ->label('Students under guardianship')
                                ->relationship('students', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->options(fn (): array => Student::query()
                                    ->with('studentClass')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Student $student): array => [$student->getKey() => $student->selectLabel()])
                                    ->all())
                                ->getOptionLabelFromRecordUsing(fn (Student $record): string => $record->selectLabel())
                                ->createOptionAction(fn (\Filament\Actions\Action $action): \Filament\Actions\Action => $action->label('New student'))
                                ->createOptionForm([
                                    TextInput::make('gr_no')
                                        ->label('GR #')
                                        ->required()
                                        ->maxLength(50)
                                        ->unique(table: 'students'),
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
                                        ->required()
                                        ->default(today()),
                                ])
                                ->helperText('Link existing students, or create one inline. Labels show the class and GR # so same-named students are never mixed up.'),
                            WizardSubmitActions::make(),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
