<?php

declare(strict_types=1);

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use App\Models\Admin;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\StaffAssignmentRequest;
use App\Models\StudentClass;
use App\Models\Subject;
use Illuminate\Database\UniqueConstraintViolationException;

it('blocks a second teacher from being assigned the same class and subject', function (): void {
    $firstTeacher = Staff::factory()->create();
    $secondTeacher = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    StaffAssignment::query()->create([
        'staff_id' => $firstTeacher->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    StaffAssignment::query()->create([
        'staff_id' => $secondTeacher->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);
})->throws(UniqueConstraintViolationException::class);

it('lets the same teacher keep their assignment when editing without changes', function (): void {
    $teacher = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    $assignment = StaffAssignment::query()->create([
        'staff_id' => $teacher->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    $assignment->update(['staff_id' => $teacher->getKey()]);

    expect(StaffAssignment::query()->count())->toBe(1);
});

it('skips creating an assignment on request approval when the pair already has a teacher', function (): void {
    $owner = Staff::factory()->create();
    $requester = Staff::factory()->create();
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    StaffAssignment::query()->create([
        'staff_id' => $owner->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    $request = StaffAssignmentRequest::query()->create([
        'staff_id' => $requester->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'action' => AssignmentAction::Add->value,
        'status' => AssignmentRequestStatus::Pending->value,
    ]);

    $request->approve($admin);

    expect(StaffAssignment::query()->where('student_class_id', $class->getKey())->where('subject_id', $subject->getKey())->count())->toBe(1)
        ->and(StaffAssignment::query()->where('staff_id', $requester->getKey())->count())->toBe(0)
        ->and($request->refresh()->status)->toBe(AssignmentRequestStatus::Approved);
});

it('creates the assignment on request approval when the pair is unowned', function (): void {
    $requester = Staff::factory()->create();
    $admin = Admin::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    $request = StaffAssignmentRequest::query()->create([
        'staff_id' => $requester->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'action' => AssignmentAction::Add->value,
        'status' => AssignmentRequestStatus::Pending->value,
    ]);

    $request->approve($admin);

    expect(StaffAssignment::query()->where('staff_id', $requester->getKey())->count())->toBe(1);
});
