<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Tables;

use App\Enums\FeeStructureType;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class FeeStructuresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (FeeStructureType $state): string => $state->label()),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(FeeStructureType::cases())
                        ->mapWithKeys(fn (FeeStructureType $type): array => [$type->value => $type->label()])
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
