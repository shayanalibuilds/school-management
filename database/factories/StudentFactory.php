<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
final class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sr_no' => null,
            'name' => fake()->name(),
            'student_class_id' => StudentClass::factory(),
            'joining_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'leaving_date' => null,
            'status' => StudentStatus::Active,
        ];
    }

    /**
     * Indicate that the student has left the school.
     */
    public function left(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StudentStatus::Left,
            'leaving_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the student has graduated.
     */
    public function graduated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StudentStatus::Graduated,
            'leaving_date' => fake()->dateTimeBetween('-2 years', '-1 year'),
        ]);
    }
}
