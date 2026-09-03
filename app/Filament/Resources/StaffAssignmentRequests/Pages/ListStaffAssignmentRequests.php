<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignmentRequests\Pages;

use App\Filament\Resources\StaffAssignmentRequests\StaffAssignmentRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListStaffAssignmentRequests extends ListRecords
{
    protected static string $resource = StaffAssignmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
