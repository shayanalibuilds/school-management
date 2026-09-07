<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payrolls\Tables;

use App\Enums\PayrollStatus;
use App\Models\Admin;
use App\Models\Payroll;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

final class PayrollsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff.name')
                    ->label('Staff member')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('month')
                    ->label('Month')
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PayrollStatus $state): string => $state->label())
                    ->color(fn (PayrollStatus $state): string => match ($state) {
                        PayrollStatus::Paid => 'success',
                        PayrollStatus::Pending => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Paid at')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PayrollStatus::cases())
                        ->mapWithKeys(fn (PayrollStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Mark paid')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Payroll $record): bool => $record->status === PayrollStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (Payroll $record): void {
                        if (! auth('admin')->user() instanceof Admin) {
                            throw new AuthorizationException();
                        }

                        $record->markPaid();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('markPaid')
                    ->label('Mark paid')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(
                        /** @param Collection<int, Payroll> $records */
                        function (Collection $records): void {
                            if (! auth('admin')->user() instanceof Admin) {
                                throw new AuthorizationException();
                            }

                            foreach ($records as $record) {
                                if (! $record instanceof Payroll) {
                                    continue;
                                }

                                $record->markPaid();
                            }
                        },
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
