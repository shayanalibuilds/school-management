<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The teacher cannot change this name later; it comes from the roster.'),
                TextInput::make('cnic')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('35202-1234567-1')
                    ->maxLength(15)
                    ->helperText('The teacher enters this CNIC on the registration page to set up their account.'),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),
            ])
            ->columns(1);
    }
}
