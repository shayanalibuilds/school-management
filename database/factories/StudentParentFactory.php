<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StudentParent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentParent>
 */
final class StudentParentFactory extends Factory
{
    protected $model = StudentParent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'cnic' => fake()->unique()->numerify('#####-#######-#'),
            'phone' => fake()->numerify('03#########'),
            'occupation' => fake()->randomElement(['Teacher', 'Shopkeeper', 'Engineer', 'Driver', null]),
        ];
    }
}
