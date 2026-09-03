<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamResults\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class ExamResultForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentYear = (int) today()->year;

        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('year')
                    ->options(collect(range($currentYear - 9, $currentYear))
                        ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                        ->all())
                    ->default((string) $currentYear)
                    ->required(),
                TextInput::make('marks')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->rule(static fn ($get): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        $total = (float) ($get('total_marks') ?? 100);

                        if ($total > 0 && (float) $value > $total) {
                            $fail('Marks cannot exceed the total marks.');
                        }
                    }),
                TextInput::make('total_marks')
                    ->numeric()
                    ->minValue(1)
                    ->default(100)
                    ->required(),
            ]);
    }
}
