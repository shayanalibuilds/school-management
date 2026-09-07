<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Staff;
use App\Policies\Concerns\SchoolRecordPermissions;

/**
 * Staff records are never deleted: the delete abilities are denied
 * for every user, matching the school-wide "nothing is ever removed"
 * rule (records archive instead). See SchoolRecordPermissions.
 */
final class StaffPolicy
{
    use SchoolRecordPermissions;
}
