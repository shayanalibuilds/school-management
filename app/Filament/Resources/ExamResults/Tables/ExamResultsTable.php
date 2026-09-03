<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamResults\Tables;

use App\Support\Grades;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ExamResultsTable
{
    public static function configure(Table $table): Table
    {
        $currentYear = (int) today()->year;

        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentClass.name')
                    ->label('Class')
                    ->badge(),
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->badge(),
                TextColumn::make('year')
                    ->sortable(),
                TextColumn::make('marks')
                    ->sortable(),
                TextColumn::make('total_marks')
                    ->label('Total'),
                TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->state(fn ($record): string => Grades::fromMarks($record->marks, $record->total_marks))
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'A') => 'success',
                        $state === 'B' || $state === 'C' => 'info',
                        $state === 'D' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('studentClass')
                    ->relationship('studentClass', 'name')
                    ->label('Class'),
                SelectFilter::make('year')
                    ->options(collect(range($currentYear - 9, $currentYear))
                        ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                        ->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
