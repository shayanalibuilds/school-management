<?php

declare(strict_types=1);

namespace App\Filament\Staff\Resources\MyRequests\Tables;

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use App\Models\StaffAssignmentRequest;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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
                        AssignmentRequestStatus::Withdrawn => 'gray',
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
                SelectFilter::make('status')
                    ->options(collect(AssignmentRequestStatus::cases())
                        ->mapWithKeys(fn (AssignmentRequestStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                // A teacher can quietly pull back requests the admin has not
                // reviewed yet. Nothing in the school is ever deleted: the
                // request keeps its row and shows as Withdrawn.
                BulkAction::make('withdraw')
                    ->label('Withdraw')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Unreviewed requests are marked as withdrawn. Requests that were already approved or rejected stay untouched.')
                    ->modalSubmitActionLabel('Withdraw')
                    ->action(
                        /** @param Collection<int, StaffAssignmentRequest> $records */
                        function (Collection $records): void {
                            foreach ($records as $record) {
                                if (! $record instanceof StaffAssignmentRequest) {
                                    continue;
                                }

                                $isOwnPending = $record->staff_id === auth('staff')->id()
                                    && $record->status === AssignmentRequestStatus::Pending;

                                if ($isOwnPending) {
                                    $record->update(['status' => AssignmentRequestStatus::Withdrawn]);
                                }
                            }
                        },
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
