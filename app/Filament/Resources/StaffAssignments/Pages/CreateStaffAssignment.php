<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignments\Pages;

use App\Filament\Resources\StaffAssignments\StaffAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateStaffAssignment extends CreateRecord
{
    protected static string $resource = StaffAssignmentResource::class;
}
