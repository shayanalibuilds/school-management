<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fee_id',
        'provider',
        'payer_name',
        'payer_cnic',
        'payer_phone',
        'amount',
        'reference',
        'status',
        'paid_at',
        'gateway_payload',
    ];

    /**
     * @return BelongsTo<Fee, $this>
     */
    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    public function complete(string $reference, array $payload = []): void
    {
        if ($this->status === PaymentStatus::Completed) {
            return;
        }

        $this->update([
            'status' => PaymentStatus::Completed,
            'reference' => $reference,
            'paid_at' => now(),
            'gateway_payload' => $payload,
        ]);

        $this->fee->recordPaymentAmount((float) $this->amount);
    }

    public function fail(string $reference, array $payload = []): void
    {
        if ($this->status === PaymentStatus::Completed) {
            return;
        }

        $this->update([
            'status' => PaymentStatus::Failed,
            'reference' => $reference,
            'gateway_payload' => $payload,
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }
}
