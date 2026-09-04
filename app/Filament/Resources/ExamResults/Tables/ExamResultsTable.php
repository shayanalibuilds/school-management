<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExamResults\Tables;

use App\Models\ExamResult;
use App\Support\Grades;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class ExamResultsTable
{
    public static function configure(Table $table): Table
    {
        $currentYear = (int) today()->year;

        return $table
            ->columns([
                TextColumn::make('student.sr_no')
                    ->label('SR #')
                    ->searchable()
                    ->sortable(),
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
                SelectFilter::make('subject')
                    ->relationship('subject', 'name'),
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
                BulkAction::make('bulkEdit')
                    ->label('Bulk edit')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        Select::make('student_class_id')
                            ->label('Class')
                            ->relationship('studentClass', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Only filled fields are applied to the selected results.'),
                        Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('year')
                            ->options(collect(range($currentYear - 9, $currentYear))
                                ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                                ->all()),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $payload = collect($data)->filter(fn (mixed $value): bool => filled($value))->all();

                        $records->each(fn (ExamResult $record) => $record->update($payload));
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
