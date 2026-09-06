<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

final class ExamResultsPublishedNotification extends Notification
{
    public function __construct(
        public string $className,
        public string $subjectName,
        public string $publisherName,
    ) {}

    /**
     * Database channel only: the notification bell on every admin and
     * staff device picks it up within the polling interval, and devices
     * that were offline receive it as soon as they reconnect - the
     * notification is persisted until it is read.
     *
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
            // 'format' => 'filament' makes the alert visible in the
            // Filament database notification bell on every device.
            'format' => 'filament',
            'title' => 'Exam results published',
            'body' => sprintf(
                '%s results for "%s" were published by %s. Students can see them and corrections stay open for 30 days.',
                $this->subjectName,
                $this->className,
                $this->publisherName,
            ),
            'icon' => 'heroicon-o-academic-cap',
            'color' => 'success',
        ];
    }
}
