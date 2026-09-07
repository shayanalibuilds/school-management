<?php

declare(strict_types=1);

namespace App\Filament\Resources\Timetables\Schemas;

use App\Filament\Support\WizardSubmitActions;
use App\Models\Subject;
use App\Models\TimetableSlot;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class TimetableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Class & subject')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            Select::make('student_class_id')
                                ->label('Class')
                                ->relationship('studentClass', 'name')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required(),
                            Select::make('subject_id')
                                ->label('Subject')
                                ->options(fn ($get): array => Subject::query()
                                    ->when($get('student_class_id') !== null, fn ($query) => $query
                                        ->whereHas('studentClasses', fn ($classes) => $classes
                                            ->where('student_classes.id', (string) $get('student_class_id'))))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText('Only subjects assigned to the selected class are listed.'),
                        ]),
                    Step::make('Time slot')
                        ->icon('heroicon-m-clock')
                        ->schema([
                            Select::make('day_of_week')
                                ->label('Day')
                                ->options(TimetableSlot::dayLabels())
                                ->required(),
                            TimePicker::make('start_time')
                                ->label('Starts at')
                                ->seconds(false)
                                ->required()
                                ->rule(static fn (?TimetableSlot $record, $get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($record, $get): void {
                                    if ($value === null || $get('end_time') === null) {
                                        return;
                                    }

                                    if ((string) $get('end_time') <= (string) $value) {
                                        $fail('The end time must be after the start time.');

                                        return;
                                    }

                                    $classId = $get('student_class_id');
                                    $subjectId = $get('subject_id');

                                    if ($classId === null || $subjectId === null) {
                                        return;
                                    }

                                    $conflict = TimetableSlot::findConflict(
                                        (string) $classId,
                                        (string) $subjectId,
                                        (int) $get('day_of_week'),
                                        (string) $value,
                                        (string) $get('end_time'),
                                        $record?->getKey(),
                                    );

                                    if ($conflict instanceof TimetableSlot) {
                                        $fail(sprintf(
                                            '%s is already booked: %s teaches %s to %s at %s-%s. A class can only have one subject in a slot, and a teacher can only teach one class at a time.',
                                            $conflict->dayLabel(),
                                            $conflict->teacher()?->name ?? 'a teacher',
                                            $conflict->subject?->name,
                                            $conflict->studentClass?->name,
                                            mb_substr((string) $conflict->start_time, 0, 5),
                                            mb_substr((string) $conflict->end_time, 0, 5),
                                        ));
                                    }
                                }),
                            TimePicker::make('end_time')
                                ->label('Ends at')
                                ->seconds(false)
                                ->required(),
                            TextInput::make('room')
                                ->label('Room')
                                ->maxLength(50)
                                ->placeholder('e.g. Room 12'),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
