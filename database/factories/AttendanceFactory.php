<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
final class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'student_class_id' => fn (array $attributes): string => Student::query()->find($attributes['student_id'])->student_class_id,
            'staff_id' => Staff::factory(),
            'date' => today(),
            'status' => fake()->randomElement(AttendanceStatus::cases()),
        ];
    }
}
