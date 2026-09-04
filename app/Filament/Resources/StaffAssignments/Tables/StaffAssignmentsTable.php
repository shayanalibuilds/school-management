<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignments\Tables;

use App\Filament\Support\BulkEdit;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class StaffAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff.name')
                    ->label('Staff member')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentClass.name')
                    ->label('Class')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('staff')
                    ->relationship('staff', 'name'),
                SelectFilter::make('studentClass')
                    ->relationship('studentClass', 'name')
                    ->label('Class'),
                SelectFilter::make('subject')
                    ->relationship('subject', 'name'),
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
                        Select::make('staff_id')
                            ->label('Staff member')
                            ->relationship('staff', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Only filled fields are applied to the selected assignments.'),
                        Select::make('student_class_id')
                            ->label('Class')
                            ->relationship('studentClass', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        BulkEdit::apply($records, $data);
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
