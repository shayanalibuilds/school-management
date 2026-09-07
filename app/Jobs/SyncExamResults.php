<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\ExamResultsSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncExamResults implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string|float|null>  $marks
     */
    public function __construct(
        public string $markerType,
        public string $markerId,
        public string $classId,
        public string $subjectId,
        public int $year,
        public array $marks,
    ) {}

    public function handle(): void
    {
        ExamResultsSync::execute($this->markerType, $this->markerId, $this->classId, $this->subjectId, $this->year, $this->marks);
    }
}
