<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamResults\Schemas;

use App\Filament\Support\WizardSubmitActions;
use App\Models\ExamResult;
use App\Models\Student;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class ExamResultForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentYear = (int) today()->year;

        $locked = fn (?ExamResult $record): bool => $record instanceof ExamResult && ! $record->isEditable();

        return $schema
            ->components([
                Wizard::make([
                    Step::make('Exam details')
                        ->icon('heroicon-m-book-open')
                        ->schema([
                            Select::make('student_id')
                                ->label('Student')
                                ->options(fn (): array => Student::query()
                                    ->with('studentClass')
                                    ->active()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Student $student): array => [$student->getKey() => $student->selectLabel()])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled($locked),
                            Select::make('subject_id')
                                ->label('Subject')
                                ->relationship('subject', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled($locked),
                            Select::make('year')
                                ->options(collect(range($currentYear - 9, $currentYear))
                                    ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                                    ->all())
                                ->default((string) $currentYear)
                                ->required()
                                ->disabled($locked),
                        ])
                        ->columns(2),
                    Step::make('Marks')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->schema([
                            TextInput::make('marks')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->rule(static fn ($get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $total = (float) ($get('total_marks') ?? 100);

                                    if ($total > 0 && (float) $value > $total) {
                                        $fail('Marks cannot exceed the total marks.');
                                    }
                                })
                                ->disabled($locked),
                            TextInput::make('total_marks')
                                ->numeric()
                                ->minValue(1)
                                ->default(100)
                                ->required()
                                ->disabled($locked),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
