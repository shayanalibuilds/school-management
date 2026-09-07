<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\ExamResultStatus;
use App\Filament\Pages\AppSettingsPage;
use App\Filament\Pages\FillAttendance;
use App\Filament\Pages\FillExamResults;
use App\Jobs\PublishAllExamResults;
use App\Jobs\SyncAttendance;
use App\Jobs\SyncExamResults;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Support\AppSettings;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('persists the queue everything toggle and caches the read', function (): void {
    expect(AppSettings::queueEverything())->toBeFalse();

    AppSettings::set(AppSettings::QUEUE_EVERYTHING, '1');

    expect(AppSettings::queueEverything())->toBeTrue()
        ->and(AppSettings::get(AppSettings::QUEUE_EVERYTHING))->toBe('1');

    AppSettings::set(AppSettings::QUEUE_EVERYTHING, '0');

    expect(AppSettings::queueEverything())->toBeFalse();
});

it('queues attendance writes when queue everything is on', function (): void {
    Queue::fake();

    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);
    $studentId = (string) $student->getKey();

    AppSettings::set(AppSettings::QUEUE_EVERYTHING, '1');

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(FillAttendance::class)
        ->set('classId', $class->getKey())
        ->set('statuses.'.$studentId, AttendanceStatus::Present->value)
        ->call('save')
        ->assertNotified('Attendance queued');

    Queue::assertPushed(SyncAttendance::class);

    // Nothing written synchronously.
    expect(Attendance::query()->count())->toBe(0);

    // Processing the job writes the rows and notifies every admin.
    new SyncAttendance('admin', (string) $admin->getKey(), (string) $class->getKey(), [
        $studentId => AttendanceStatus::Present->value,
    ])->handle();

    expect(Attendance::query()->count())->toBe(1)
        ->and($admin->notifications()->count())->toBe(1);
});

it('keeps attendance writes direct when queue everything is off', function (): void {
    Queue::fake();

    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);
    $studentId = (string) $student->getKey();

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(FillAttendance::class)
        ->set('classId', $class->getKey())
        ->set('statuses.'.$studentId, AttendanceStatus::Present->value)
        ->call('save')
        ->assertNotified('Attendance filled');

    Queue::assertNothingPushed();

    expect(Attendance::query()->count())->toBe(1);
});

it('queues results saves and publishing when queue everything is on', function (): void {
    Queue::fake();

    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);
    $studentId = (string) $student->getKey();

    AppSettings::set(AppSettings::QUEUE_EVERYTHING, '1');

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    $page = Livewire::test(FillExamResults::class)
        ->set('classId', $class->getKey())
        ->set('subjectId', $subject->getKey())
        ->set('year', '2026')
        ->set('marks.'.$studentId, '80')
        ->call('save')
        ->assertNotified('Results queued');

    Queue::assertPushed(SyncExamResults::class);

    expect(ExamResult::query()->count())->toBe(0);

    // Processing the save writes draft rows in the background.
    new SyncExamResults('admin', (string) $admin->getKey(), (string) $class->getKey(), (string) $subject->getKey(), 2026, [
        $studentId => '80',
    ])->handle();

    expect(ExamResult::query()->count())->toBe(1);

    // Publishing is queued as well - publication is school-wide.
    $page->call('publishAll')->assertNotified('Publishing queued');

    Queue::assertPushed(PublishAllExamResults::class);

    new PublishAllExamResults('admin', (string) $admin->getKey(), 2026)->handle();

    expect(ExamResult::query()->where('status', ExamResultStatus::Published->value)->count())->toBe(1)
        ->and(ExamResult::query()->whereNotNull('published_at')->count())->toBe(1);
});

it('toggles the setting from the app settings page', function (): void {
    $admin = Admin::factory()->create();

    actingAs($admin, 'admin');
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire::test(AppSettingsPage::class)
        ->set('queueEverything', true);

    expect(AppSettings::queueEverything())->toBeTrue();

    Livewire::test(AppSettingsPage::class)
        ->set('queueEverything', false);

    expect(AppSettings::queueEverything())->toBeFalse();
});

it('shows the app settings page in the settings group', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    get('/dashboard/app-settings-page')->assertOk();
});
