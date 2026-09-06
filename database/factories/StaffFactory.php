<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StaffStatus;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
final class StaffFactory extends Factory
{
    protected $model = Staff::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'cnic' => fake()->unique()->numerify('#####-#######-#'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'phone' => fake()->numerify('03#########'),
            'joining_date' => fake()->dateTimeBetween('-5 years', '-1 year'),
            'status' => StaffStatus::Active,
        ];
    }
}
