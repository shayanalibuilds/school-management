<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class AttendanceFilledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $className,
        public string $markerName,
    ) {}

    /**
     * Database channel only: the notification bell on every admin
     * device picks it up within the polling interval, and devices
     * that were offline receive it as soon as they reconnect -
     * the notification is persisted until it is read.
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
            'title' => 'Attendance filled',
            'body' => sprintf(
                'Attendance for "%s" was filled today by %s.',
                $this->className,
                $this->markerName,
            ),
            'icon' => 'heroicon-o-clipboard-document-check',
            'color' => 'success',
            'date' => today()->toDateString(),
        ];
    }
}
