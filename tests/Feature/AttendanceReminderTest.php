<?php

declare(strict_types=1);

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\artisan;

it('emails and database-notifies admins when a class has no attendance today', function (): void {
    Notification::fake();

    $class = StudentClass::factory()->create(['name' => 'Class 5']);
    Student::factory()->count(2)->create(['student_class_id' => $class->getKey()]);

    $admin = Admin::factory()->create();

    artisan('attendance:check-deadline');

    Notification::assertSentTo($admin, App\Notifications\AttendanceDeadlineNotification::class, fn (App\Notifications\AttendanceDeadlineNotification $notification, array $channels): bool => $channels === ['mail', 'database']
        && $notification->toMail($admin)->subject === 'Attendance not submitted for Class 5'
        && $notification->toArray($admin)['title'] === 'Attendance not submitted');
});

it('stays quiet when every class has submitted attendance today', function (): void {
    Notification::fake();

    $class = StudentClass::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    Attendance::query()->create([
        'student_id' => $student->getKey(),
        'student_class_id' => $class->getKey(),
        'admin_id' => Admin::factory()->create()->getKey(),
        'date' => today(),
        'status' => App\Enums\AttendanceStatus::Present,
    ]);

    $admin = Admin::factory()->create();

    artisan('attendance:check-deadline');

    Notification::assertNothingSent();
});

it('is scheduled daily at 08:20 in the app timezone', function (): void {
    $event = collect(Schedule::events())
        ->first(fn (Illuminate\Console\Scheduling\Event $event): bool => str_contains((string) $event->command, 'attendance:check-deadline'));

    expect($event)->not->toBeNull();

    assert($event !== null);

    $timezone = $event->timezone;
    $timezoneName = $timezone instanceof DateTimeZone ? $timezone->getName() : $timezone;

    expect($event->expression)->toBe('20 8 * * *')
        ->and($timezoneName)->toBe(config('app.timezone', 'Asia/Karachi'));
});
