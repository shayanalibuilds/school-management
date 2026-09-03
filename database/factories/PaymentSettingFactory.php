<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentProvider;
use App\Models\PaymentSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentSetting>
 */
final class PaymentSettingFactory extends Factory
{
    protected $model = PaymentSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => PaymentProvider::JazzCash,
            'environment' => 'sandbox',
            'is_active' => true,
            'credentials' => [
                'merchant_id' => 'MC-'.fake()->numerify('#####'),
                'password' => fake()->bothify('********'),
                'integrity_salt' => fake()->bothify('************'),
            ],
        ];
    }

    public function jazzcash(): static
    {
        return $this->state(fn (): array => [
            'provider' => PaymentProvider::JazzCash,
            'credentials' => [
                'merchant_id' => 'MC-12345',
                'password' => 'secret123',
                'integrity_salt' => 'saltabc12345',
            ],
        ]);
    }

    public function easypaisa(): static
    {
        return $this->state(fn (): array => [
            'provider' => PaymentProvider::EasyPaisa,
            'credentials' => [
                'store_id' => '4444',
                'hash_key' => 'hashkey123456',
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
