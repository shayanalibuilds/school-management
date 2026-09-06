<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeeStructureType;
use App\Models\FeeStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeStructure>
 */
final class FeeStructureFactory extends Factory
{
    protected $model = FeeStructure::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Tuition Fee', 'Admission Fee', 'Exam Fee', 'Transport Fee', 'Annual Charges']),
            'type' => FeeStructureType::Monthly,
            'amount' => fake()->randomElement([1500, 2000, 2500, 3000, 5000]),
        ];
    }

    public function oneTime(): static
    {
        return $this->state(fn (): array => [
            'type' => FeeStructureType::OneTime,
        ]);
    }
}
