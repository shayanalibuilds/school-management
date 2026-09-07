<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attendance;
use App\Policies\Concerns\SchoolRecordPermissions;

/**
 * Attendance records are never deleted: the delete abilities are denied
 * for every user, matching the school-wide "nothing is ever removed"
 * rule (records archive instead). See SchoolRecordPermissions.
 */
final class AttendancePolicy
{
    use SchoolRecordPermissions;
}
