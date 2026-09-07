<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Tables;

use App\Filament\Support\ArchiveAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SubjectsTable
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
                    ->label('Subject name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentClasses.name')
                    ->label('Classes')
                    ->badge()
                    ->limitList(3),
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
