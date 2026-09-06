<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignments\Pages;

use App\Filament\Resources\StaffAssignments\StaffAssignmentResource;
use App\Filament\Support\CreateWizardRecord;

final class CreateStaffAssignment extends CreateWizardRecord
{
    protected static string $resource = StaffAssignmentResource::class;
}
