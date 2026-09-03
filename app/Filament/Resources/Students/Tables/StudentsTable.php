<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Tables;

use App\Enums\StudentStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sr_no')
                    ->label('SR #')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentClass.name')
                    ->label('Class')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StudentStatus $state): string => $state->label())
                    ->color(fn (StudentStatus $state): string => match ($state) {
                        StudentStatus::Active => 'success',
                        StudentStatus::Graduated => 'info',
                        StudentStatus::Left => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('joining_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('studentClass')
                    ->relationship('studentClass', 'name')
                    ->label('Class'),
                SelectFilter::make('status')
                    ->options(collect(StudentStatus::cases())
                        ->mapWithKeys(fn (StudentStatus $status): array => [$status->value => $status->label()])
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
