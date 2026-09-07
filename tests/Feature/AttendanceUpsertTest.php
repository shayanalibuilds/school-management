<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentClass;
use App\Support\AttendanceSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * The fill-attendance pages must never blow up with "UNIQUE constraint
 * failed: attendances.student_id, attendances.date" — saving a class
 * twice (or a queued sync landing while an admin submits inline) has
 * to update the existing row instead of trying to create a duplicate.
 */
it('updates the existing row when attendance is already recorded for that day', function (): void {
    $student = Student::factory()->create();
    $date = today()->toDateString();

    $created = Attendance::updateOrCreateForDay($student->getKey(), $date, [
        'student_class_id' => $student->student_class_id,
        'staff_id' => null,
        'admin_id' => null,
        'status' => AttendanceStatus::Present->value,
    ]);

    $updated = Attendance::updateOrCreateForDay($student->getKey(), $date, [
        'student_class_id' => $student->student_class_id,
        'staff_id' => null,
        'admin_id' => null,
        'status' => AttendanceStatus::Absent->value,
    ]);

    expect(Attendance::query()->where('student_id', $student->getKey())->count())->toBe(1)
        ->and($updated->is($created))->toBeTrue()
        ->and($updated->status)->toBe(AttendanceStatus::Absent);
});

it('creates the row when none exists yet', function (): void {
    $student = Student::factory()->create();

    $attendance = Attendance::updateOrCreateForDay($student->getKey(), today()->toDateString(), [
        'student_class_id' => $student->student_class_id,
        'staff_id' => null,
        'admin_id' => null,
        'status' => AttendanceStatus::Leave->value,
    ]);

    expect(Attendance::query()->count())->toBe(1)
        ->and($attendance->exists)->toBeTrue()
        ->and($attendance->status)->toBe(AttendanceStatus::Leave);
});

it('absorbs a concurrent writer by updating its row instead of failing', function (): void {
    $student = Student::factory()->create();
    $date = today()->toDateString();
    $concurrentWriterHasSnuckIn = true;

    // Simulate a queued sync job inserting the row between our lookup
    // and our save: the very first fresh-model save inserts a
    // conflicting row first, so our INSERT hits the unique constraint
    // and the retry path has to take over.
    Attendance::saving(function (Attendance $attendance) use (&$concurrentWriterHasSnuckIn, $student, $date): void {
        if (! $concurrentWriterHasSnuckIn || $attendance->exists) {
            return;
        }

        $concurrentWriterHasSnuckIn = false;

        DB::statement(
            'INSERT INTO attendances (id, student_id, student_class_id, admin_id, date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [(string) Str::uuid(), $student->getKey(), $student->student_class_id, null, $date.' 00:00:00', 'absent', now(), now()],
        );
    });

    $attendance = Attendance::updateOrCreateForDay($student->getKey(), $date, [
        'student_class_id' => $student->student_class_id,
        'staff_id' => null,
        'admin_id' => null,
        'status' => AttendanceStatus::Leave->value,
    ]);

    expect(Attendance::query()->where('student_id', $student->getKey())->count())->toBe(1)
        ->and($attendance->status)->toBe(AttendanceStatus::Leave);
});

it('writes the same class twice without duplicating rows or errors', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    $class = StudentClass::factory()->create();
    $students = Student::factory()->count(3)->create(['student_class_id' => $class->getKey()]);

    $morning = $students
        ->mapWithKeys(fn (Student $student): array => [(string) $student->getKey() => AttendanceStatus::Present->value])
        ->all();

    $afternoon = $students
        ->mapWithKeys(fn (Student $student): array => [(string) $student->getKey() => AttendanceStatus::Absent->value])
        ->all();

    $first = AttendanceSync::execute('admin', Admin::query()->firstOrFail()->getKey(), (string) $class->getKey(), $morning);
    $second = AttendanceSync::execute('admin', Admin::query()->firstOrFail()->getKey(), (string) $class->getKey(), $afternoon);

    expect($first)->toBe(3)
        ->and($second)->toBe(3)
        ->and(Attendance::query()->count())->toBe(3)
        ->and(Attendance::query()->where('status', AttendanceStatus::Absent)->count())->toBe(3);
});

it('matches rows stored with legacy date formats', function (): void {
    $student = Student::factory()->create();
    $date = today()->toDateString();

    // A row written by an older build that stored a bare date string.
    DB::statement(
        'INSERT INTO attendances (id, student_id, student_class_id, admin_id, date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [(string) Str::uuid(), $student->getKey(), $student->student_class_id, null, $date, 'present', now(), now()],
    );

    $attendance = Attendance::updateOrCreateForDay($student->getKey(), $date, [
        'student_class_id' => $student->student_class_id,
        'staff_id' => null,
        'admin_id' => null,
        'status' => AttendanceStatus::Leave->value,
    ]);

    expect(Attendance::query()->where('student_id', $student->getKey())->count())->toBe(1)
        ->and($attendance->status)->toBe(AttendanceStatus::Leave);
});

it('normalises mixed legacy date formats into one canonical shape', function (): void {
    $class = StudentClass::factory()->create();
    $students = Student::factory()->count(2)->create(['student_class_id' => $class->getKey()]);

    DB::statement(
        'INSERT INTO attendances (id, student_id, student_class_id, admin_id, date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [(string) Str::uuid(), $students[0]->getKey(), $class->getKey(), null, '2026-09-01', 'present', now(), now()],
    );

    DB::statement(
        'INSERT INTO attendances (id, student_id, student_class_id, admin_id, date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [(string) Str::uuid(), $students[1]->getKey(), $class->getKey(), null, '2026-09-01 15:39:11', 'present', now(), now()],
    );

    $migration = require database_path('migrations/2026_09_07_000003_normalize_attendance_dates.php');
    $migration->up();

    $dates = DB::table('attendances')->orderBy('student_id')->pluck('date');

    expect($dates)->each->toBe('2026-09-01 00:00:00');
});
