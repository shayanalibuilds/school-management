<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PaymentSetting extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'environment',
        'is_active',
        'credentials',
    ];

    /**
     * Whether all required credentials for the provider are present.
     */
    public function hasCompleteCredentials(): bool
    {
        foreach ($this->provider->requiredCredentials() as $key) {
            if (empty($this->credentials[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'is_active' => 'boolean',
            'credentials' => 'encrypted:array',
        ];
    }
}
