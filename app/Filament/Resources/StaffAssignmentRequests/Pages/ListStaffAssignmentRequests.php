<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignmentRequests\Pages;

use App\Filament\Resources\StaffAssignmentRequests\StaffAssignmentRequestResource;
use Filament\Resources\Pages\ListRecords;

final class ListStaffAssignmentRequests extends ListRecords
{
    protected static string $resource = StaffAssignmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        // Requests are raised by staff on their own panel; admins only
        // approve or reject them, so there is no create button here.
        return [];
    }
}
