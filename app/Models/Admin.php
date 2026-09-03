<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class Admin extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory;
    use HasUuids;
    use Notifiable;

    protected $table = 'admins';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
        return $panel->getId() === 'admin';
    }

    #[Override]
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
