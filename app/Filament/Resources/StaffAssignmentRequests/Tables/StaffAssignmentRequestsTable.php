<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignmentRequests\Tables;

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use App\Models\Admin;
use App\Models\StaffAssignmentRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

final class StaffAssignmentRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff.name')
                    ->label('Staff member')
                    ->searchable()
                    ->sortable(),
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
                TextColumn::make('reason')
                    ->limit(50)
                    ->toggleable(),
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
                SelectFilter::make('status')
                    ->options(collect(AssignmentRequestStatus::cases())
                        ->mapWithKeys(fn (AssignmentRequestStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (StaffAssignmentRequest $record): bool => $record->status === AssignmentRequestStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (StaffAssignmentRequest $record): void {
                        $admin = auth('admin')->user();

                        if (! $admin instanceof Admin) {
                            throw new AuthorizationException();
                        }

                        $record->approve($admin);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (StaffAssignmentRequest $record): bool => $record->status === AssignmentRequestStatus::Pending)
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Reason for rejection')
                            ->maxLength(500),
                    ])
                    ->action(function (StaffAssignmentRequest $record, array $data): void {
                        $admin = auth('admin')->user();

                        if (! $admin instanceof Admin) {
                            throw new AuthorizationException();
                        }

                        $record->reject($admin, $data['admin_note'] ?? null);
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $admin = auth('admin')->user();

                        if (! $admin instanceof Admin) {
                            throw new AuthorizationException();
                        }

                        $records->each(fn (StaffAssignmentRequest $record) => $record->approve($admin));
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->form([
                        Textarea::make('admin_note')
                            ->label('Reason for rejection')
                            ->maxLength(500),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $admin = auth('admin')->user();

                        if (! $admin instanceof Admin) {
                            throw new AuthorizationException();
                        }

                        $records->each(fn (StaffAssignmentRequest $record) => $record->reject($admin, $data['admin_note'] ?? null));
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
