<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentClasses\Schemas;

use App\Filament\Support\WizardSubmitActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class StudentClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Class details')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            TextInput::make('name')
                                ->label('Class name')
                                ->required()
                                ->maxLength(100)
                                ->unique(ignoreRecord: true),
                        ]),
                    Step::make('Subjects')
                        ->icon('heroicon-m-book-open')
                        ->schema([
                            Select::make('subjects')
                                ->label('Subjects taught in this class')
                                ->relationship('subjects', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->createOptionAction(fn (\Filament\Actions\Action $action): \Filament\Actions\Action => $action->label('New subject'))
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Subject name')
                                        ->required()
                                        ->maxLength(100)
                                        ->unique(table: 'subjects'),
                                ])
                                ->helperText('Tick every subject this class studies, or create a new subject inline.'),
                            WizardSubmitActions::make(),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
