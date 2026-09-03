<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StaffStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class Staff extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'staffs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cnic',
        'email',
        'password',
        'phone',
        'joining_date',
        'leaving_date',
        'status',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    #[Override]
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'staff';
    }

    #[Override]
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<StaffAssignment, $this>
     */
    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffAssignment::class, 'staff_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'joining_date' => 'date',
            'leaving_date' => 'date',
            'status' => StaffStatus::class,
        ];
    }
}
