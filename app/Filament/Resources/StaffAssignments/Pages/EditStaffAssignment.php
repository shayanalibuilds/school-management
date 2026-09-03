<?php

declare(strict_types=1);

namespace App\Filament\Resources\StaffAssignments\Pages;

use App\Filament\Resources\StaffAssignments\StaffAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditStaffAssignment extends EditRecord
{
    protected static string $resource = StaffAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
