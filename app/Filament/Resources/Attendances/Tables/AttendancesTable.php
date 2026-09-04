<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendances\Tables;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class AttendancesTable
{
    public static function configure(Table $table): Table
    {
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
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (AttendanceStatus $state): string => $state->label())
                    ->color(fn (AttendanceStatus $state): string => match ($state) {
                        AttendanceStatus::Present => 'success',
                        AttendanceStatus::Leave => 'warning',
                        AttendanceStatus::Absent => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('markedBy.name')
                    ->label('Marked by')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('studentClass')
                    ->relationship('studentClass', 'name')
                    ->label('Class'),
                SelectFilter::make('status')
                    ->options(collect(AttendanceStatus::cases())
                        ->mapWithKeys(fn (AttendanceStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                Filter::make('date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $from): Builder => $query->whereDate('date', '>=', $from),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $until): Builder => $query->whereDate('date', '<=', $until),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('bulkEdit')
                    ->label('Bulk edit')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        DatePicker::make('date')
                            ->helperText('Only filled fields are applied to the selected records.'),
                        Select::make('status')
                            ->options(collect(AttendanceStatus::cases())
                                ->mapWithKeys(fn (AttendanceStatus $status): array => [$status->value => $status->label()])
                                ->all()),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $payload = collect($data)->filter(fn (mixed $value): bool => filled($value))->all();

                        $records->each(fn (Attendance $record) => $record->update($payload));
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
