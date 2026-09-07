<?php

declare(strict_types=1);

use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Models\TimetableSlot;
use Illuminate\Database\UniqueConstraintViolationException;

function timetableSlot(StudentClass $class, Subject $subject, int $day, string $start, string $end, ?string $room = null): TimetableSlot
{
    return TimetableSlot::query()->create([
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'day_of_week' => $day,
        'start_time' => $start,
        'end_time' => $end,
        'room' => $room,
    ]);
}

it('creates timetable slots for a class and subject', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    $slot = timetableSlot($class, $subject, 1, '09:00:00', '10:00:00', 'Room 12');

    expect($slot->dayLabel())->toBe('Monday')
        ->and($slot->studentClass->is($class))->toBeTrue()
        ->and($slot->subject->is($subject))->toBeTrue()
        ->and($slot->room)->toBe('Room 12');
});

it('blocks two subjects for the same class in the same time slot', function (): void {
    $class = StudentClass::factory()->create();
    $english = Subject::factory()->create();
    $maths = Subject::factory()->create();

    timetableSlot($class, $english, 1, '09:00:00', '10:00:00');

    timetableSlot($class, $maths, 1, '09:00:00', '10:00:00');
})->throws(UniqueConstraintViolationException::class);

it('allows the same subject in two classes at the same time when different teachers are assigned', function (): void {
    $classOne = StudentClass::factory()->create();
    $classTwo = StudentClass::factory()->create();
    $english = Subject::factory()->create();
    $teacherOne = Staff::factory()->create();
    $teacherTwo = Staff::factory()->create();

    StaffAssignment::query()->create(['staff_id' => $teacherOne->getKey(), 'student_class_id' => $classOne->getKey(), 'subject_id' => $english->getKey()]);
    StaffAssignment::query()->create(['staff_id' => $teacherTwo->getKey(), 'student_class_id' => $classTwo->getKey(), 'subject_id' => $english->getKey()]);

    timetableSlot($classOne, $english, 1, '09:00:00', '10:00:00');

    // Multiple rooms: the same subject can run for another class at once.
    $slot = timetableSlot($classTwo, $english, 1, '09:00:00', '10:00:00');

    expect($slot->exists)->toBeTrue()
        ->and(TimetableSlot::findConflict($classTwo->getKey(), $english->getKey(), 1, '09:00:00', '10:00:00', $slot->getKey()))->toBeNull();
});

it('blocks two classes at the same time when the same teacher is assigned to both', function (): void {
    $classOne = StudentClass::factory()->create();
    $classTwo = StudentClass::factory()->create();
    $english = Subject::factory()->create();
    $maths = Subject::factory()->create();
    $teacher = Staff::factory()->create();

    StaffAssignment::query()->create(['staff_id' => $teacher->getKey(), 'student_class_id' => $classOne->getKey(), 'subject_id' => $english->getKey()]);
    StaffAssignment::query()->create(['staff_id' => $teacher->getKey(), 'student_class_id' => $classTwo->getKey(), 'subject_id' => $maths->getKey()]);

    timetableSlot($classOne, $english, 1, '09:00:00', '10:00:00');

    // One teacher cannot be in two classrooms at once.
    $conflict = TimetableSlot::findConflict($classTwo->getKey(), $maths->getKey(), 1, '09:30:00', '10:30:00');

    expect($conflict)->not->toBeNull()
        ->and($conflict->student_class_id)->toBe($classOne->getKey());
});

it('detects overlapping time ranges for the same class', function (): void {
    $class = StudentClass::factory()->create();
    $english = Subject::factory()->create();
    $maths = Subject::factory()->create();

    timetableSlot($class, $english, 1, '09:00:00', '10:00:00');

    $conflict = TimetableSlot::findConflict(
        $class->getKey(),
        $maths->getKey(),
        1,
        '09:30:00',
        '10:30:00',
    );

    expect($conflict)->not->toBeNull()
        ->and($conflict->subject_id)->toBe($english->getKey());
});

it('lets another class use the same slot freely when no teacher is assigned', function (): void {
    $classOne = StudentClass::factory()->create();
    $classTwo = StudentClass::factory()->create();
    $english = Subject::factory()->create();
    $history = Subject::factory()->create();

    timetableSlot($classOne, $english, 2, '11:00:00', '12:00:00');

    // Another subject in class two at the same time is fine.
    expect(TimetableSlot::findConflict($classTwo->getKey(), $history->getKey(), 2, '11:00:00', '12:00:00'))->toBeNull();

    // The same subject in class two also works - different rooms, no teacher assigned.
    expect(TimetableSlot::findConflict($classTwo->getKey(), $english->getKey(), 2, '11:30:00', '12:30:00'))->toBeNull();
});

it('allows adjacent slots and different days without conflicts', function (): void {
    $class = StudentClass::factory()->create();
    $english = Subject::factory()->create();
    $maths = Subject::factory()->create();

    timetableSlot($class, $english, 1, '09:00:00', '10:00:00');

    expect(TimetableSlot::findConflict($class->getKey(), $maths->getKey(), 1, '10:00:00', '11:00:00'))->toBeNull()
        ->and(TimetableSlot::findConflict($class->getKey(), $english->getKey(), 2, '09:00:00', '10:00:00'))->toBeNull();
});

it('resolves the assigned teacher for a slot', function (): void {
    $teacher = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    Student::factory()->count(2)->create(['student_class_id' => $class->getKey()]);

    StaffAssignment::query()->create([
        'staff_id' => $teacher->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    $slot = timetableSlot($class, $subject, 3, '08:00:00', '08:45:00');

    expect($slot->teacher()?->is($teacher))->toBeTrue();
});

it('ignores its own row when editing a slot', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    $slot = timetableSlot($class, $subject, 4, '09:00:00', '10:00:00');

    $conflict = TimetableSlot::findConflict(
        $class->getKey(),
        $subject->getKey(),
        4,
        '09:00:00',
        '10:00:00',
        $slot->getKey(),
    );

    expect($conflict)->toBeNull();
});
