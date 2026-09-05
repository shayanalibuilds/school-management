<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Tables;

use App\Enums\StudentStatus;
use App\Models\Student;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('gr_no')
                    ->label('GR #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentClass.name')
                    ->label('Class')
                    ->badge(),
                TextColumn::make('parents.name')
                    ->label('Parents')
                    ->badge()
                    ->color('gray')
                    ->limit(30)
                    ->toggleable(),
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
                    ->action(
                        /** @param Collection<int, Student> $records */
                        function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        },
                    )
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
