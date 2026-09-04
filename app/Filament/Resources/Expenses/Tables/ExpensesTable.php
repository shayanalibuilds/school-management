<?php

declare(strict_types=1);

namespace App\Filament\Resources\Expenses\Tables;

use App\Enums\ExpenseRecurrence;
use App\Filament\Support\BulkEdit;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('recurrence')
                    ->badge()
                    ->formatStateUsing(fn (ExpenseRecurrence $state): string => $state->label()),
            ])
            ->filters([
                SelectFilter::make('recurrence')
                    ->options(collect(ExpenseRecurrence::cases())
                        ->mapWithKeys(fn (ExpenseRecurrence $recurrence): array => [$recurrence->value => $recurrence->label()])
                        ->all()),
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
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('PKR')
                            ->helperText('Only filled fields are applied to the selected expenses.'),
                        Select::make('recurrence')
                            ->options(collect(ExpenseRecurrence::cases())
                                ->mapWithKeys(fn (ExpenseRecurrence $recurrence): array => [$recurrence->value => $recurrence->label()])
                                ->all()),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        BulkEdit::apply($records, $data);
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
