<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
final class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'English', 'Urdu', 'Mathematics', 'Science', 'Islamiat',
                'Social Studies', 'Computer Science', 'Physics', 'Chemistry',
            ]),
        ];
    }
}
