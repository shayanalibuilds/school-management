<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExpenseRecurrence;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
final class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Electricity Bill', 'Water Bill', 'Stationery', 'Furniture Repair', 'Internet']),
            'description' => fake()->sentence(),
            'amount' => fake()->numberBetween(500, 20000),
            'recurrence' => fake()->randomElement(ExpenseRecurrence::cases()),
        ];
    }
}
