<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GradingScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradingScale>
 */
final class GradingScaleFactory extends Factory
{
    protected $model = GradingScale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['A++', 'A+', 'A', 'B', 'C', 'D']),
            'min_percentage' => fake()->numberBetween(40, 99),
            'sort_order' => 0,
        ];
    }
}
