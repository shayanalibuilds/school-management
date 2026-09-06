<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Schemas;

use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Subject details')
                        ->icon('heroicon-m-book-open')
                        ->schema([
                            TextInput::make('name')
                                ->label('Subject name')
                                ->required()
                                ->maxLength(100)
                                ->unique(ignoreRecord: true),
                        ]),
                    Step::make('Classes')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            Select::make('studentClasses')
                                ->label('Classes studying this subject')
                                ->relationship('studentClasses', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->createOptionAction(fn (\Filament\Actions\Action $action): \Filament\Actions\Action => $action->label('New class'))
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Class name')
                                        ->required()
                                        ->maxLength(100)
                                        ->unique(table: 'student_classes'),
                                ])
                                ->helperText('Tick every class that studies this subject, or create a new class inline.'),
                            WizardSubmitActions::make(),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
