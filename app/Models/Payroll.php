<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property numeric-string $amount
 * @property string $month
 * @property Carbon|null $paid_at
 * @property PayrollStatus $status
 */
final class Payroll extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'month',
        'amount',
        'status',
        'paid_at',
    ];

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function markPaid(): void
    {
        if ($this->status === PayrollStatus::Paid) {
            return;
        }

        $this->update([
            'status' => PayrollStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PayrollStatus::class,
            'paid_at' => 'datetime',
        ];
    }
}
