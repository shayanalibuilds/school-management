<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StudentClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentClass>
 */
final class StudentClassFactory extends Factory
{
    protected $model = StudentClass::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Class '.fake()->unique()->numberBetween(1, 100),
        ];
    }
}
