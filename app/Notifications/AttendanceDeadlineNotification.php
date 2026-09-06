<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\StudentClass;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AttendanceDeadlineNotification extends Notification
{
    use Queueable;

    public function __construct(
        public StudentClass $studentClass,
    ) {}

    /**
     * Email keeps admins informed even when they are offline from the
     * panel; the database channel feeds the in-app notification bell
     * so the alert is waiting on every device when they reconnect.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Attendance not submitted for '.$this->studentClass->name)
            ->greeting('Hello!')
            ->line(sprintf(
                'Attendance for "%s" has not been marked today (%s) before the 8:20 AM deadline.',
                $this->studentClass->name,
                today()->toDateString(),
            ))
            ->line('Please fill today\'s attendance so the record stays complete.')
            ->action('Fill attendance', route('filament.admin.pages.fill-attendance'))
            ->line('You are receiving this because you are an administrator of the school management system.');
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
