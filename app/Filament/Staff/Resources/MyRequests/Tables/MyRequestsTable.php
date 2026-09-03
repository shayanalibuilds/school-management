<?php

declare(strict_types=1);

namespace App\Filament\Staff\Resources\MyRequests\Tables;

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class MyRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query
                ->where('staff_id', auth('staff')->id()))
            ->columns([
                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(fn (AssignmentAction $state): string => $state->label()),
                TextColumn::make('studentClass.name')
                    ->label('Class')
                    ->badge(),
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (AssignmentRequestStatus $state): string => $state->label())
                    ->color(fn (AssignmentRequestStatus $state): string => match ($state) {
                        AssignmentRequestStatus::Pending => 'warning',
                        AssignmentRequestStatus::Approved => 'success',
                        AssignmentRequestStatus::Rejected => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('admin_note')
                    ->label('Admin note')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ]);
    }
}
