<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Tables;

use App\Filament\Support\ArchiveAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class GuardiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Active' : 'Inactive')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cnic')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->toggleable(),
                TextColumn::make('relation')
                    ->label('Relation')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Students')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                ArchiveAction::make(),
            ])
            ->toolbarActions([
                ArchiveAction::bulk(),
            ]);
    }
}
