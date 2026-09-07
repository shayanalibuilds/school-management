<?php

declare(strict_types=1);

namespace App\Filament\Resources\Attendances\Tables;

use App\Enums\AttendanceStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AttendancesTable
{
    public static function configure(Table $table): Table
    {
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
                TextColumn::make('markerName')
                    ->label('Marked by')
                    ->state(fn ($record): string => $record->markerName() ?? '—')
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
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if (is_string($from) && $from !== '') {
                            $query->whereDate('date', '>=', $from);
                        }

                        if (is_string($until) && $until !== '') {
                            $query->whereDate('date', '<=', $until);
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
            ]);
    }
}
