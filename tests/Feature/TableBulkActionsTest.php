<?php

declare(strict_types=1);

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use App\Enums\PaymentStatus;
use App\Enums\StudentStatus;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\ExamResults\Pages\ListExamResults;
use App\Filament\Resources\Fees\Pages\ListFees;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payrolls\Pages\ListPayrolls;
use App\Filament\Resources\StaffAssignmentRequests\Pages\ListStaffAssignmentRequests;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\StaffAssignmentRequest;
use App\Models\Student;
use App\Models\Subject;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('bulk changes the status of many students at once', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $students = Student::factory()->count(3)->create();

    Livewire::test(ListStudents::class)
        ->callTableBulkAction('changeStatus', $students, ['status' => StudentStatus::Left->value]);

    $students->each(fn (Student $student) => expect($student->refresh()->status)->toBe(StudentStatus::Left));
});

it('no longer offers bulk edit on the students table', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(ListStudents::class)
        ->assertTableBulkActionDoesNotExist('bulkEdit');
});

it('bulk archives students instead of deleting them', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $students = Student::factory()->count(2)->create();

    Livewire::test(ListStudents::class)
        ->callTableBulkAction('archive', $students);

    // Rows stay in the database - only the status flips to the
    // archived value, because nothing in a school may be deleted.
    $students->each(fn (Student $student) => expect(Student::query()->find($student->getKey()))->not->toBeNull()
        ->and($student->fresh()->status->value)->toBe('left'));
});

it('bulk marks pending payments as completed and credits the fee', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $fee = Fee::factory()->create(['amount' => 5000, 'amount_paid' => 0]);
    $payments = Payment::factory()->count(2)->create([
        'fee_id' => $fee->getKey(),
        'amount' => 1000,
        'status' => PaymentStatus::Pending,
    ]);

    Livewire::test(ListPayments::class)
        ->callTableBulkAction('markCompleted', $payments);

    expect((float) $fee->refresh()->amount_paid)->toBe(2000.0);

    $payments->each(fn (Payment $payment) => expect($payment->refresh()->status)->toBe(PaymentStatus::Completed));
});

it('bulk marks pending payrolls as paid', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $payrolls = Payroll::factory()->count(2)->create();

    Livewire::test(ListPayrolls::class)
        ->callTableBulkAction('markPaid', $payrolls);

    $payrolls->each(fn (Payroll $payroll) => expect($payroll->refresh()->status->value)->toBe('paid')
        ->and($payroll->fresh()->paid_at)->not->toBeNull());
});

it('shows and searches the student GR # on fees, attendance and results tables', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $student = Student::factory()->create(['gr_no' => 'GR-4242']);
    $subject = Subject::factory()->create(['name' => 'Bulk Subject']);
    Fee::factory()->create(['student_id' => $student->getKey()]);
    Attendance::factory()->create(['student_id' => $student->getKey()]);
    ExamResult::factory()->create(['student_id' => $student->getKey(), 'subject_id' => $subject->getKey()]);

    Livewire::test(ListFees::class)
        ->assertSee('GR-4242')
        ->searchTable('GR-4242')
        ->assertSee($student->name);

    Livewire::test(ListAttendances::class)
        ->assertSee('GR-4242');

    Livewire::test(ListExamResults::class)
        ->assertSee('GR-4242')
        ->assertSee('Bulk Subject');
});

it('bulk approves and rejects assignment requests with an admin note', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $requests = StaffAssignmentRequest::factory()->count(2)->create([
        'action' => AssignmentAction::Add->value,
        'status' => AssignmentRequestStatus::Pending,
    ]);

    Livewire::test(ListStaffAssignmentRequests::class)
        ->callTableBulkAction('approve', $requests);

    $requests->each(fn (StaffAssignmentRequest $request) => expect($request->refresh()->status)->toBe(AssignmentRequestStatus::Approved));

    $pending = StaffAssignmentRequest::factory()->create([
        'action' => AssignmentAction::Remove->value,
        'status' => AssignmentRequestStatus::Pending,
    ]);

    Livewire::test(ListStaffAssignmentRequests::class)
        ->callTableBulkAction('reject', [$pending], ['admin_note' => 'Already covered by another teacher.']);

    expect($pending->refresh()->status)->toBe(AssignmentRequestStatus::Rejected)
        ->and($pending->fresh()->admin_note)->toBe('Already covered by another teacher.');
});

it('hides the new assignment request button from admins', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(ListStaffAssignmentRequests::class)
        ->assertActionDoesNotExist('create');
});
