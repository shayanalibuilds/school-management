<?php

declare(strict_types=1);

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use App\Filament\Staff\Resources\MyRequests\Pages\CreateMyRequest;
use App\Models\Admin;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\StaffAssignmentRequest;
use App\Models\StudentClass;
use App\Models\Subject;
use Filament\Facades\Filament;
use Illuminate\Database\UniqueConstraintViolationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('assigns a staff member to a class and subject', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    $assignment = StaffAssignment::create([
        'staff_id' => $staff->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    expect($staff->assignments()->count())->toBe(1)
        ->and($assignment->staff->is($staff))->toBeTrue()
        ->and($assignment->studentClass->is($class))->toBeTrue()
        ->and($assignment->subject->is($subject))->toBeTrue();
});

it('prevents duplicate assignments for the same class and subject', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    StaffAssignment::create([
        'staff_id' => $staff->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);

    StaffAssignment::create([
        'staff_id' => $staff->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
    ]);
})->throws(UniqueConstraintViolationException::class);

it('lets a teacher request a new assignment and admin approves it', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    $request = StaffAssignmentRequest::create([
        'staff_id' => $staff->getKey(),
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'action' => AssignmentAction::Add,
        'status' => AssignmentRequestStatus::Pending,
    ]);

    expect($request->status)->toBe(AssignmentRequestStatus::Pending)
        ->and($staff->assignments()->count())->toBe(0);

    $request->approve(Admin::factory()->create());

    expect($request->refresh()->status)->toBe(AssignmentRequestStatus::Approved)
        ->and($staff->assignments()->where('student_class_id', $class->getKey())->where('subject_id', $subject->getKey())->exists())->toBeTrue();
});

it('lets an admin reject a request with a note', function (): void {
    $request = StaffAssignmentRequest::factory()->create([
        'action' => AssignmentAction::Add,
    ]);

    $request->reject(Admin::factory()->create(), 'Teacher already teaches this class.');

    expect($request->refresh()->status)->toBe(AssignmentRequestStatus::Rejected)
        ->and($request->admin_note)->toBe('Teacher already teaches this class.')
        ->and($request->reviewed_at)->not->toBeNull();
});

it('approving a removal request deletes the assignment', function (): void {
    $staff = Staff::factory()->create();
    $assignment = StaffAssignment::factory()->create(['staff_id' => $staff->getKey()]);

    $request = StaffAssignmentRequest::create([
        'staff_id' => $staff->getKey(),
        'student_class_id' => $assignment->student_class_id,
        'subject_id' => $assignment->subject_id,
        'action' => AssignmentAction::Remove,
        'target_staff_assignment_id' => $assignment->getKey(),
        'status' => AssignmentRequestStatus::Pending,
    ]);

    $request->approve(Admin::factory()->create());

    expect($staff->assignments()->count())->toBe(0);
});

it('scopes assignment requests to the signed-in staff member', function (): void {
    $staff = Staff::factory()->create();
    StaffAssignmentRequest::factory()->count(2)->create(['staff_id' => $staff->getKey()]);
    StaffAssignmentRequest::factory()->count(3)->create();

    actingAs($staff, 'staff');
    Filament::setCurrentPanel('staff');

    Livewire::test(App\Filament\Staff\Resources\MyRequests\Pages\ListMyRequests::class)
        ->assertCanSeeTableRecords(StaffAssignmentRequest::query()->where('staff_id', $staff->getKey())->get())
        ->assertCanNotSeeTableRecords(StaffAssignmentRequest::query()->where('staff_id', '!=', $staff->getKey())->get());
});

it('sets the staff member automatically when a teacher submits a request', function (): void {
    $staff = Staff::factory()->create();
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    actingAs($staff, 'staff');
    Filament::setCurrentPanel('staff');

    Livewire::test(CreateMyRequest::class)
        ->fillForm([
            'action' => AssignmentAction::Add->value,
            'student_class_id' => $class->getKey(),
            'subject_id' => $subject->getKey(),
            'reason' => 'I have experience teaching this.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $request = StaffAssignmentRequest::query()->sole();

    expect($request->staff_id)->toBe($staff->getKey())
        ->and($request->status)->toBe(AssignmentRequestStatus::Pending);
});

it('shows the assignments page to staff', function (): void {
    actingAs(Staff::factory()->create(), 'staff');

    get('/staff/my-assignments')->assertOk();
});
