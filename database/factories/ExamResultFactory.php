<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExamResult;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamResult>
 */
final class ExamResultFactory extends Factory
{
    protected $model = ExamResult::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'student_class_id' => fn (array $attributes): string => Student::query()->find($attributes['student_id'])->student_class_id,
            'subject_id' => Subject::factory(),
            'year' => (int) today()->year,
            'marks' => fake()->numberBetween(40, 100),
            'total_marks' => 100,
            'status' => \App\Enums\ExamResultStatus::Draft->value,
            'published_at' => null,
        ];
    }

    /**
     * A published result with a fresh 30-day correction window.
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => \App\Enums\ExamResultStatus::Published->value,
            'published_at' => now(),
        ]);
    }
}
