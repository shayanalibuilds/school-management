<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Shared permission shape for every school record.
 *
 * Viewing and day-to-day work (create, update) stays open to the
 * panel users. Deletion is closed at the policy level for every
 * single record: nobody — admin or staff — can delete anything.
 * "Removing" a record means archiving it (a status change), which is
 * handled by the Archive actions and the
 * ArchivesInsteadOfDeleting model concern, never by a delete.
 */
trait SchoolRecordPermissions
{
    public function viewAny(object $user): bool
    {
        return true;
    }

    public function view(object $user, Model $record): bool
    {
        return true;
    }

    public function create(object $user): bool
    {
        return true;
    }

    public function update(object $user, Model $record): bool
    {
        return true;
    }

    public function delete(object $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(object $user): bool
    {
        return false;
    }

    public function forceDelete(object $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(object $user): bool
    {
        return false;
    }
}
