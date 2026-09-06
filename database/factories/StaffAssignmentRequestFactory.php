<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use App\Models\Staff;
use App\Models\StaffAssignmentRequest;
use App\Models\StudentClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffAssignmentRequest>
 */
final class StaffAssignmentRequestFactory extends Factory
{
    protected $model = StaffAssignmentRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'student_class_id' => StudentClass::factory(),
            'subject_id' => Subject::factory(),
            'action' => AssignmentAction::Add,
            'target_staff_assignment_id' => null,
            'reason' => fake()->sentence(),
            'status' => AssignmentRequestStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_note' => null,
        ];
    }
}
