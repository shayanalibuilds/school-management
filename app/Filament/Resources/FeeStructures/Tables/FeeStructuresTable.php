<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Tables;

use App\Enums\FeeStructureType;
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
                BulkAction::make('bulkEdit')
                    ->label('Bulk edit')
                    ->icon('heroicon-m-pencil-square')
                    ->form([
                        Select::make('type')
                            ->options(collect(FeeStructureType::cases())
                                ->mapWithKeys(fn (FeeStructureType $type): array => [$type->value => $type->label()])
                                ->all())
                            ->helperText('Only filled fields are applied to the selected fee heads.'),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('PKR'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        BulkEdit::apply($records, $data);
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
