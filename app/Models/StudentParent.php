<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class StudentParent extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'parents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'cnic',
        'phone',
        'occupation',
    ];

    /**
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id');
    }
}
