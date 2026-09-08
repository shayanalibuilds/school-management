<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignmentRequests;

use App\Filament\Resources\StaffAssignmentRequests\Pages\ListStaffAssignmentRequests;
use App\Filament\Resources\StaffAssignmentRequests\Tables\StaffAssignmentRequestsTable;
use App\Models\StaffAssignmentRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class StaffAssignmentRequestResource extends Resource
{
    protected static ?string $model = StaffAssignmentRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Assignment Requests';

    public static function table(Table $table): Table
    {
        return StaffAssignmentRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffAssignmentRequests::route('/'),
        ];
    }
}
