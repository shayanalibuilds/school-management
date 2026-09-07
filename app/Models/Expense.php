<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseRecurrence;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property numeric-string $amount
 * @property Carbon|null $created_at
 * @property string $name
 * @property ExpenseRecurrence $recurrence
 */
final class Expense extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'amount',
        'recurrence',
    ];

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'recurrence' => ExpenseRecurrence::class,
        ];
    }
}
