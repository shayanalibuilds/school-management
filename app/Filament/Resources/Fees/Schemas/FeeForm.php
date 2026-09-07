<?php

declare(strict_types=1);

namespace App\Filament\Resources\Fees\Schemas;

use App\Filament\Support\WizardSubmitActions;
use App\Models\Fee;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

final class FeeForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentYear = (int) today()->year;

        return $schema
            ->components([
                Wizard::make([
                    Step::make('Fee assignment')
                        ->icon('heroicon-m-user-group')
                        ->schema([
                            Select::make('student_id')
                                ->label('Student')
                                ->options(function (?Fee $record): array {
                                    $labels = Student::query()
                                        ->with('studentClass')
                                        ->active()
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Student $student): array => [$student->getKey() => $student->selectLabel()])
                                        ->all();

                                    // A fee belonging to a graduated or
                                    // departed student still needs its label
                                    // on the edit form instead of a raw id.
                                    if ($record instanceof Fee && ! array_key_exists($record->student_id, $labels) && $record->student !== null) {
                                        $labels[$record->student_id] = $record->student->selectLabel();
                                    }

                                    return $labels;
                                })
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('fee_structure_id')
                                ->label('Fee structure')
                                ->relationship('feeStructure', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('year')
                                ->options(collect(range($currentYear - 9, $currentYear + 1))
                                    ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                                    ->all())
                                ->default((string) $currentYear)
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Amount & due date')
                        ->icon('heroicon-m-banknotes')
                        ->schema([
                            TextInput::make('amount')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('PKR')
                                ->required()
                                ->helperText('Pre-filled from the fee structure when the page loads; adjust if needed.'),
                            DatePicker::make('due_date'),
                            WizardSubmitActions::make(),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
