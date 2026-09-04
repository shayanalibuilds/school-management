<?php

declare(strict_types=1);

namespace App\Filament\Resources\Parents\Tables;

use App\Models\StudentParent;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class ParentsTable
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
                TextColumn::make('occupation')
                    ->toggleable(),
                TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Children')
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
                            ->helperText('Only filled fields are applied to the selected parents.'),
                        TextInput::make('occupation')
                            ->maxLength(255),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $payload = collect($data)->filter(fn (mixed $value): bool => filled($value))->all();

                        $records->each(fn (StudentParent $record) => $record->update($payload));
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
