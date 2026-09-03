<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\StudentClass;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class AttendanceDeadlineNotification extends Notification
{
    use Queueable;

    public function __construct(
        public StudentClass $studentClass,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Attendance not submitted',
            'body' => sprintf(
                'Attendance for "%s" has not been marked today (%s) before the 8:20 AM deadline.',
                $this->studentClass->name,
                today()->toDateString(),
            ),
            'student_class_id' => $this->studentClass->getKey(),
            'date' => today()->toDateString(),
        ];
    }
}
