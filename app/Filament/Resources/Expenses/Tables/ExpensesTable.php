<?php

declare(strict_types=1);

namespace App\Filament\Resources\Expenses\Tables;

use App\Enums\ExpenseRecurrence;
use App\Models\Expense;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
            ])
            ->toolbarActions([
                BulkAction::make('changeRecurrence')
                    ->label('Change recurrence')
                    ->icon('heroicon-m-arrow-path')
                    ->form([
                        Select::make('recurrence')
                            ->options(collect(ExpenseRecurrence::cases())
                                ->mapWithKeys(fn (ExpenseRecurrence $recurrence): array => [$recurrence->value => $recurrence->label()])
                                ->all())
                            ->required(),
                    ])
                    ->action(
                        /** @param Collection<int, Expense> $records */
                        function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['recurrence' => $data['recurrence']]);
                            }
                        },
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
