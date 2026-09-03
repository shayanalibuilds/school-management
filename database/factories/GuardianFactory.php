<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
final class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'cnic' => fake()->unique()->numerify('#####-#######-#'),
            'phone' => fake()->numerify('03#########'),
            'relation' => fake()->randomElement(['Uncle', 'Aunt', 'Grandfather', 'Grandmother', 'Family Friend']),
        ];
    }
}
