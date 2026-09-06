<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fee_id' => Fee::factory(),
            'provider' => PaymentProvider::EasyPaisa,
            'payer_name' => fake()->name(),
            'payer_cnic' => fake()->numerify('#####-#######-#'),
            'payer_phone' => fake()->numerify('03#########'),
            'amount' => 2500,
            'reference' => null,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'gateway_payload' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Completed,
            'reference' => 'TXN-'.fake()->unique()->numerify('########'),
            'paid_at' => now(),
        ]);
    }

    public function jazzcash(): static
    {
        return $this->state(fn (): array => [
            'provider' => PaymentProvider::JazzCash,
        ]);
    }
}
