<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * School records are never hard-deleted. Deleting (from the UI, from
 * code, from a bulk action) flips the record's status to its archived
 * value instead, so history stays intact and every relation keeps
 * working. The archive value is domain-specific: students become
 * "left", staff "resigned", configuration records "inactive".
 */
trait ArchivesInsteadOfDeleting
{
    /**
     * The status an archived record carries.
     */
    abstract protected function archiveStatus(): string;

    /**
     * The status a live record carries.
     */
    abstract protected function activeStatus(): string;

    public static function bootArchivesInsteadOfDeleting(): void
    {
        static::deleting(function (Model $record): bool {
            $record->archive();

            // Cancels the hard delete - the row stays in the table.
            return false;
        });
    }

    /**
     * Archive the record: keep the row, flip the status.
     */
    public function archive(): void
    {
        $this->forceFill(['status' => $this->archiveStatus()])->save();
    }

    /**
     * Bring an archived record back.
     */
    public function unarchive(): void
    {
        $this->forceFill(['status' => $this->activeStatus()])->save();
    }
}
