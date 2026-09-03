<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Fee extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fee_structure_id',
        'student_id',
        'year',
        'amount',
        'amount_paid',
        'status',
        'due_date',
        'paid_at',
    ];

    /**
     * @return BelongsTo<FeeStructure, $this>
     */
    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recordPaymentAmount(float|int $amount): void
    {
        $this->amount_paid = (float) $this->amount_paid + $amount;
        $this->save();
    }

    protected static function booted(): void
    {
        self::saving(function (Fee $fee): void {
            $paid = (float) $fee->amount_paid;

            if ($paid >= (float) $fee->amount && (float) $fee->amount > 0) {
                $fee->status = FeeStatus::Paid;
                $fee->paid_at ??= now();
            } elseif ($paid > 0) {
                $fee->status = FeeStatus::Partial;
            } else {
                $fee->status = FeeStatus::Unpaid;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'status' => FeeStatus::class,
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }
}
