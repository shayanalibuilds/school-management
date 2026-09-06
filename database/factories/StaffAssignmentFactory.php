<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\StudentClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffAssignment>
 */
final class StaffAssignmentFactory extends Factory
{
    protected $model = StaffAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'student_class_id' => StudentClass::factory(),
            'subject_id' => Subject::factory(),
        ];
    }
}
