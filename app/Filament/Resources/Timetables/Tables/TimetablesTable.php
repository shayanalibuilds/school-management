<?php

declare(strict_types=1);

namespace App\Filament\Resources\Timetables\Tables;

use App\Models\TimetableSlot;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class TimetablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('studentClass.name')
                    ->label('Class')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->badge(),
                TextColumn::make('teacher_name')
                    ->label('Teacher')
                    ->placeholder('Unassigned')
                    ->state(fn (TimetableSlot $record): string => $record->teacher()?->name ?? ''),
                TextColumn::make('day_of_week')
                    ->label('Day')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => TimetableSlot::dayLabels()[$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('From')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('To')
                    ->time('H:i'),
                TextColumn::make('room')
                    ->label('Room')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('studentClass')
                    ->relationship('studentClass', 'name')
                    ->label('Class'),
                SelectFilter::make('day_of_week')
                    ->label('Day')
                    ->options(TimetableSlot::dayLabels()),
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
