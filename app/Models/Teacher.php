<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A roster entry the admin creates before the teacher ever touches the
 * system. The teacher proves their CNIC on the registration page and only
 * then receives a sign-in account, which keeps the roster in charge of who
 * may register. The name lives here: it can only be changed by editing this
 * record, and any change flows into the linked staff account.
 */
final class Teacher extends Model
{
    /** @use HasFactory<\Database\Factories\TeacherFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cnic',
        'phone',
    ];

    /**
     * @return HasOne<Staff, $this>
     */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Whether this roster entry already owns a sign-in account.
     */
    public function isRegistered(): bool
    {
        return $this->staff()->exists();
    }

    protected static function booted(): void
    {
        self::updated(function (Teacher $teacher): void {
            if (! $teacher->wasChanged(['name'])) {
                return;
            }

            $staff = $teacher->staff()->first();

            if ($staff === null) {
                return;
            }

            // Only the roster may rename a staff account; the flag is what
            // lets the change past Staff's name guard.
            Staff::renamingFromRoster(fn () => $staff->forceFill(['name' => $teacher->name])->save());
        });
    }
}
