<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cnic')
                    ->label('CNIC')
                    ->searchable(),
                TextColumn::make('phone')
                    ->placeholder('—'),
                TextColumn::make('staff.email')
                    ->label('Account')
                    ->badge()
                    ->color('success')
                    ->placeholder('Not registered'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
