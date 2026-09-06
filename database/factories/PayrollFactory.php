<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
final class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'month' => today()->format('Y-m'),
            'amount' => fake()->randomElement([25000, 30000, 40000, 45000]),
            'status' => PayrollStatus::Pending,
            'paid_at' => null,
        ];
    }
}
