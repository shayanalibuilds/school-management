<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\AttendanceSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncAttendance implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $statuses
     */
    public function __construct(
        public string $markerType,
        public string $markerId,
        public string $classId,
        public array $statuses,
    ) {}

    public function handle(): void
    {
        AttendanceSync::execute($this->markerType, $this->markerId, $this->classId, $this->statuses);
    }
}
