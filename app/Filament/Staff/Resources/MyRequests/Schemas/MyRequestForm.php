<?php

declare(strict_types=1);

namespace App\Filament\Staff\Resources\MyRequests\Schemas;

use App\Enums\AssignmentAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

final class MyRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('action')
                    ->options(collect(AssignmentAction::cases())
                        ->mapWithKeys(fn (AssignmentAction $action): array => [$action->value => $action->label()])
                        ->all())
                    ->live()
                    ->default(AssignmentAction::Add->value)
                    ->required(),
                Select::make('student_class_id')
                    ->label('Class')
                    ->relationship('studentClass', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('reason')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
