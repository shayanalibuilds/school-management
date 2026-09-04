<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Tables;

use App\Filament\Support\BulkEdit;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class GuardiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('bulkEdit')
                    ->label('Bulk edit')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->helperText('Only filled fields are applied to the selected guardians.'),
                        TextInput::make('relation')
                            ->maxLength(255),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        BulkEdit::apply($records, $data);
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
