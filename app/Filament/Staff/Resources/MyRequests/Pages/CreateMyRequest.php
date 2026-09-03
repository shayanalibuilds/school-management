<?php

declare(strict_types=1);

namespace App\Filament\Staff\Resources\MyRequests\Pages;

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use App\Filament\Staff\Resources\MyRequests\MyRequestResource;
use App\Models\Staff;
use App\Models\StaffAssignment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;

final class CreateMyRequest extends CreateRecord
{
    protected static string $resource = MyRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $staff = auth('staff')->user();

        if (! $staff instanceof Staff) {
            throw new AuthorizationException();
        }

        $data['staff_id'] = $staff->getKey();
        $data['status'] = AssignmentRequestStatus::Pending->value;

        if (($data['action'] ?? null) === AssignmentAction::Remove->value) {
            $data['target_staff_assignment_id'] = StaffAssignment::query()
                ->where('staff_id', $staff->getKey())
                ->where('student_class_id', $data['student_class_id'])
                ->where('subject_id', $data['subject_id'])
                ->first()?->getKey();
        }

        return self::getModel()::create($data);
    }
}
