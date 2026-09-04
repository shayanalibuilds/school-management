<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Tables;

use App\Enums\StudentStatus;
use App\Models\Student;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sr_no')
                    ->label('SR #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentClass.name')
                    ->label('Class')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StudentStatus $state): string => $state->label())
                    ->color(fn (StudentStatus $state): string => match ($state) {
                        StudentStatus::Active => 'success',
                        StudentStatus::Graduated => 'info',
                        StudentStatus::Left => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('joining_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('studentClass')
                    ->relationship('studentClass', 'name')
                    ->label('Class'),
                SelectFilter::make('status')
                    ->options(collect(StudentStatus::cases())
                        ->mapWithKeys(fn (StudentStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ]),
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
                        Select::make('student_class_id')
                            ->label('Class')
                            ->relationship('studentClass', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Only filled fields are applied to the selected students.'),
                        DatePicker::make('leaving_date'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $payload = collect($data)->filter(fn (mixed $value): bool => filled($value))->all();

                        $records->each(fn (Student $record) => $record->update($payload));
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('changeStatus')
                    ->label('Change status')
                    ->icon('heroicon-m-flag')
                    ->form([
                        Select::make('status')
                            ->options(collect(StudentStatus::cases())
                                ->mapWithKeys(fn (StudentStatus $status): array => [$status->value => $status->label()])
                                ->all())
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $records->each(fn (Student $record) => $record->update(['status' => $data['status']]));
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
