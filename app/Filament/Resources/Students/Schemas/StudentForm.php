<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Schemas;

use App\Enums\StudentStatus;
use App\Filament\Support\WizardSubmitActions;
use App\Models\Guardian;
use App\Models\StudentParent;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Student Info')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            TextInput::make('sr_no')
                                ->label('SR #')
                                ->numeric()
                                ->unique(ignoreRecord: true)
                                ->helperText('Leave empty to assign the next sequential number.'),
                            TextInput::make('gr_no')
                                ->label('GR #')
                                ->required()
                                ->maxLength(50)
                                ->unique(ignoreRecord: true),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('student_class_id')
                                ->label('Joining class')
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
                        ])
                        ->columns(2),
                    Step::make('Personal Details')
                        ->icon('heroicon-m-user')
                        ->schema(self::personalDetails())
                        ->columns(2),
                    Step::make('Parent Info')
                        ->icon('heroicon-m-user-group')
                        ->schema([
                            Select::make('parents')
                                ->relationship('parents', 'name')
                                ->multiple()
                                ->searchable(['name', 'cnic'])
                                ->getOptionLabelFromRecordUsing(fn (StudentParent $record): string => $record->name.' — '.$record->cnic)
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')->required()->maxLength(255),
                                    TextInput::make('cnic')->required()->unique(table: 'parents')->placeholder('35202-1234567-1'),
                                    TextInput::make('phone')->required()->tel(),
                                    TextInput::make('occupation')->maxLength(255),
                                ])
                                ->helperText('Link existing parents, or create one inline. The CNIC here is what parents use on the public pages.'),
                        ]),
                    Step::make('Guardian Info')
                        ->icon('heroicon-m-user-plus')
                        ->schema([
                            Select::make('guardians')
                                ->relationship('guardians', 'name')
                                ->multiple()
                                ->searchable(['name', 'cnic'])
                                ->getOptionLabelFromRecordUsing(fn (Guardian $record): string => $record->name.' — '.$record->cnic)
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')->required()->maxLength(255),
                                    TextInput::make('cnic')->required()->unique(table: 'guardians')->placeholder('35202-1234567-1'),
                                    TextInput::make('phone')->required()->tel(),
                                    TextInput::make('relation')->maxLength(255),
                                ])
                                ->helperText('Optional. A guardian CNIC also works on the public pages.'),
                            WizardSubmitActions::make(),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    private static function personalDetails(): array
    {
        return [
            TextInput::make('father_name')
                ->maxLength(255),
            DatePicker::make('date_of_birth')
                ->maxDate(today()),
            Select::make('gender')
                ->options([
                    'male' => 'Male',
                    'female' => 'Female',
                ])
                ->native(false),
            TextInput::make('b_form_cnic')
                ->label('B-Form CNIC')
                ->placeholder('35202-1234567-1')
                ->maxLength(20),
            TextInput::make('phone')
                ->tel()
                ->maxLength(20),
            TextInput::make('previous_school')
                ->maxLength(255),
            Textarea::make('address')
                ->columnSpanFull(),
        ];
    }
}
