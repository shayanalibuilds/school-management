<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeStructureType;
use App\Enums\RecordStatus;
use App\Models\Concerns\ArchivesInsteadOfDeleting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FeeStructure extends Model
{
    use ArchivesInsteadOfDeleting;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'amount',
    ];

    /**
     * @return HasMany<Fee, $this>
     */
    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type' => FeeStructureType::class,
            'amount' => 'decimal:2',
        ];
    }

    protected function archiveStatus(): string
    {
        return RecordStatus::Inactive->value;
    }

    protected function activeStatus(): string
    {
        return RecordStatus::Active->value;
    }
}
