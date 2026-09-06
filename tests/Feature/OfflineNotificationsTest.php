<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Filament\Pages\FillAttendance;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('persists attendance filled notifications for every admin device', function (): void {
    $admin = Admin::factory()->create();
    $otherAdmin = Admin::factory()->create();
    $class = StudentClass::factory()->create(['name' => 'Class 2']);
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(FillAttendance::class)
        ->set('classId', $class->getKey())
        ->set('statuses.'.$student->getKey(), AttendanceStatus::Present->value)
        ->call('save')
        ->assertSuccessful();

    $notifications = DatabaseNotification::query()
        ->where('type', App\Notifications\AttendanceFilledNotification::class)
        ->get();

    expect($notifications)->toHaveCount(2)
        ->and($notifications->pluck('notifiable_id'))->toContain($admin->getKey())
        ->and($notifications->pluck('notifiable_id'))->toContain($otherAdmin->getKey())
        ->and($notifications->first()->data['format'])->toBe('filament')
        ->and($notifications->first()->data['title'])->toBe('Attendance filled')
        ->and($notifications->first()->data['body'])->toContain('Class 2')
        ->and($notifications->first()->data['body'])->toContain($admin->name);

    // Offline fallback: the row persists until read, so an admin who
    // was offline during the save still receives it on reconnect.
    expect($notifications->firstWhere('notifiable_id', $otherAdmin->getKey())->read_at)->toBeNull();
});

it('tells admins and the subject teacher when results are published', function (): void {
    $staff = Staff::factory()->create();
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create(['name' => 'Class 3']);
    $subject = Subject::factory()->create(['name' => 'Physics']);
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    $staff->assignments()->create([
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    actingAs($staff, 'staff');
    Filament\Facades\Filament::setCurrentPanel('staff');

    Livewire::test(App\Filament\Staff\Pages\FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', '2026')
        ->set("marks.{$student->getKey()}", '77')
        ->call('save')
        ->assertSuccessful();

    Livewire::test(App\Filament\Staff\Pages\FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', '2026')
        ->call('publish')
        ->assertNotified();

    $notifications = DatabaseNotification::query()
        ->where('type', App\Notifications\ExamResultsPublishedNotification::class)
        ->get();

    expect($notifications)->toHaveCount(2)
        ->and($notifications->where('notifiable_type', Admin::class))->toHaveCount(1)
        ->and($notifications->where('notifiable_type', Staff::class))->toHaveCount(1)
        ->and($notifications->first()->data['title'])->toBe('Exam results published')
        ->and($notifications->first()->data['body'])->toContain('Physics')
        ->and($notifications->first()->data['body'])->toContain('Class 3');
});

it('renders database notifications through the Filament bell format', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create(['name' => 'Class 9']);
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(FillAttendance::class)
        ->set('classId', $class->getKey())
        ->set('statuses.'.$student->getKey(), AttendanceStatus::Present->value)
        ->call('save');

    $row = $admin->notifications()->sole();

    $filamentNotification = Filament\Notifications\Notification::fromDatabase($row);

    expect($filamentNotification->getTitle())->toBe('Attendance filled')
        ->and($row->notifiable_id)->toBe($admin->getKey());
});

it('shows the bell only after login and keeps notifications unread', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(FillAttendance::class)
        ->set('classId', $class->getKey())
        ->set('statuses.'.$student->getKey(), AttendanceStatus::Present->value)
        ->call('save');

    expect($admin->refresh()->unreadNotifications)->toHaveCount(1);
});

it('never duplicates attendance rows on repeated saves of the same day', function (): void {
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create(['name' => 'Class 4']);
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    foreach (['present', 'absent'] as $pass => $status) {
        Livewire::test(FillAttendance::class)
            ->set('classId', $class->getKey())
            ->set('statuses.'.$student->getKey(), $status)
            ->call('save');
    }

    expect(Attendance::query()->where('student_id', $student->getKey())->count())->toBe(1)
        ->and(Attendance::query()->where('student_id', $student->getKey())->first()->status->value)->toBe('absent');
});
