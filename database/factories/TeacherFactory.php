<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
final class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'cnic' => fake()->unique()->numerify('#####-#######-#'),
            'phone' => fake()->numerify('03#########'),
        ];
    }
}
