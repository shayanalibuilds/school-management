<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MarkingScheme;
use App\Models\StudentClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarkingScheme>
 */
final class MarkingSchemeFactory extends Factory
{
    protected $model = MarkingScheme::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_class_id' => StudentClass::factory(),
            'subject_id' => Subject::factory(),
            'min_marks' => 0,
            'max_marks' => 100,
        ];
    }
}
