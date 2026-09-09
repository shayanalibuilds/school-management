<?php

declare(strict_types=1);

namespace App\Filament\Resources\Fees\Tables;

use App\Enums\FeeStatus;
use App\Models\Fee;
use App\Models\StudentClass;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class FeesTable
{
    public static function configure(Table $table): Table
    {
        $currentYear = (int) today()->year;

        return $table
            ->columns([
                TextColumn::make('student.gr_no')
                    ->label('GR #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.studentClass.name')
                    ->label('Class')
                    ->badge(),
                TextColumn::make('feeStructure.name')
                    ->label('Fee')
                    ->badge(),
                TextColumn::make('year')
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('PKR'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (FeeStatus $state): string => $state->label())
                    ->color(fn (FeeStatus $state): string => match ($state) {
                        FeeStatus::Paid => 'success',
                        FeeStatus::Partial => 'warning',
                        FeeStatus::Unpaid => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(FeeStatus::cases())
                        ->mapWithKeys(fn (FeeStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                SelectFilter::make('class')
                    ->label('Class')
                    ->options(fn (): array => StudentClass::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, mixed $classId): Builder => $query->whereHas(
                            'student',
                            fn (Builder $studentQuery): Builder => $studentQuery->where('student_class_id', $classId),
                        ),
                    )),
                SelectFilter::make('year')
                    ->options(collect(range($currentYear - 9, $currentYear + 1))
                        ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                        ->all()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // Amounts and payment state stay out of reach of a bulk
                // action on purpose; extending due dates is the safe bulk
                // fix-up, and settled fees are skipped.
                BulkAction::make('setDueDate')
                    ->label('Set due date')
                    ->icon('heroicon-m-calendar-days')
                    ->form([
                        DatePicker::make('due_date')
                            ->label('Due date')
                            ->required(),
                    ])
                    ->action(
                        /** @param Collection<int, Fee> $records */
                        function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                if (! $record instanceof Fee || $record->status === FeeStatus::Paid) {
                                    continue;
                                }

                                $record->update(['due_date' => $data['due_date']]);
                            }
                        },
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
