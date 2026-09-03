<?php

declare(strict_types=1);

namespace App\Filament\Resources\Fees\Tables;

use App\Enums\FeeStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class FeesTable
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
                SelectFilter::make('year')
                    ->options(collect(range($currentYear - 9, $currentYear + 1))
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
