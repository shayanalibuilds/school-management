<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fee.student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fee.feeStructure.name')
                    ->label('Fee')
                    ->badge(),
                TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (PaymentProvider $state): string => $state->label()),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('payer_name')
                    ->label('Payer')
                    ->toggleable(),
                TextColumn::make('reference')
                    ->fontFamily('mono')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::Completed => 'success',
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Failed => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Paid at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->options(collect(PaymentProvider::cases())
                        ->mapWithKeys(fn (PaymentProvider $provider): array => [$provider->value => $provider->label()])
                        ->all()),
                SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())
                        ->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                //
            ]);
    }
}
