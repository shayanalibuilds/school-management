<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\ExamResultsSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PublishAllExamResults implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $actorType,
        public string $actorId,
        public int $year,
    ) {}

    public function handle(): void
    {
        ExamResultsSync::publishAll(
            $this->year,
            ExamResultsSync::actorName($this->actorType, $this->actorId),
        );
    }
}
