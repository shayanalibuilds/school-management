<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeeStatus;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fee>
 */
final class FeeFactory extends Factory
{
    protected $model = Fee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fee_structure_id' => FeeStructure::factory(),
            'student_id' => Student::factory(),
            'year' => (int) today()->year,
            'amount' => 2500,
            'amount_paid' => 0,
            'status' => FeeStatus::Unpaid,
            'due_date' => today()->addDays(10),
        ];
    }
}
