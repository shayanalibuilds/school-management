<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class StudentClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Class name')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
            ]);
    }
}
