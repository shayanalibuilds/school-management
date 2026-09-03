<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeeStructures\Schemas;

use App\Enums\FeeStructureType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class FeeStructureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(150),
                Select::make('type')
                    ->options(collect(FeeStructureType::cases())
                        ->mapWithKeys(fn (FeeStructureType $type): array => [$type->value => $type->label()])
                        ->all())
                    ->default(FeeStructureType::Monthly->value)
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('PKR')
                    ->required(),
            ]);
    }
}
