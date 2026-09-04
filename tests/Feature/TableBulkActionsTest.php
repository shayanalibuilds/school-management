<?php

declare(strict_types=1);

use App\Enums\AssignmentAction;
use App\Enums\AssignmentRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\ExpenseRecurrence;
use App\Enums\FeeStructureType;
use App\Enums\PaymentStatus;
use App\Enums\StudentStatus;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\ExamResults\Pages\ListExamResults;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Resources\Fees\Pages\ListFees;
use App\Filament\Resources\FeeStructures\Pages\ListFeeStructures;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payrolls\Pages\ListPayrolls;
use App\Filament\Resources\StaffAssignmentRequests\Pages\ListStaffAssignmentRequests;
use App\Filament\Resources\StaffAssignments\Pages\ListStaffAssignments;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Expense;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\StaffAssignment;
use App\Models\StaffAssignmentRequest;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function adminForTables(): Admin
{
    return actingAs(Admin::factory()->create(), 'admin')->getArgument(0);
}

it('bulk changes the status of many students at once', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $students = Student::factory()->count(3)->create();

    Livewire::test(ListStudents::class)
        ->callTableBulkAction('changeStatus', $students, ['status' => StudentStatus::Left->value]);

    $students->each(fn (Student $student) => expect($student->fresh()->status)->toBe(StudentStatus::Left));
});

it('bulk edits the class of many students at once', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $students = Student::factory()->count(2)->create();
    $newClass = StudentClass::factory()->create();

    Livewire::test(ListStudents::class)
        ->callTableBulkAction('bulkEdit', $students, ['student_class_id' => $newClass->getKey()]);

    $students->each(fn (Student $student) => expect($student->fresh()->student_class_id)->toBe($newClass->getKey()));
});

it('bulk deletes students into the trash', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $students = Student::factory()->count(2)->create();

    Livewire::test(ListStudents::class)
        ->callTableBulkAction('delete', $students);

    $students->each(fn (Student $student) => expect(Student::query()->find($student->getKey()))->toBeNull()
        ->and(Student::withTrashed()->find($student->getKey())->trashed())->toBeTrue());
});

it('bulk edits attendance status and date', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $attendances = Attendance::factory()->count(2)->create(['status' => AttendanceStatus::Absent]);

    Livewire::test(ListAttendances::class)
        ->callTableBulkAction('bulkEdit', $attendances, [
            'status' => AttendanceStatus::Present->value,
            'date' => '2026-08-20',
        ]);

    $attendances->each(fn (Attendance $attendance) => expect($attendance->fresh()->status)->toBe(AttendanceStatus::Present)
        ->and($attendance->fresh()->date->format('Y-m-d'))->toBe('2026-08-20'));
});

it('bulk edits exam result subject and year', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $subject = Subject::factory()->create();
    $results = ExamResult::factory()->count(2)->create(['year' => 2024]);

    Livewire::test(ListExamResults::class)
        ->callTableBulkAction('bulkEdit', $results, [
            'subject_id' => $subject->getKey(),
            'year' => '2026',
        ]);

    $results->each(fn (ExamResult $result) => expect($result->fresh()->subject_id)->toBe($subject->getKey())
        ->and($result->fresh()->year)->toBe(2026));
});

it('bulk edits fee year and due date', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $fees = Fee::factory()->count(2)->create(['year' => 2024]);

    Livewire::test(ListFees::class)
        ->callTableBulkAction('bulkEdit', $fees, [
            'year' => '2026',
            'due_date' => '2026-09-30',
        ]);

    $fees->each(fn (Fee $fee) => expect($fee->fresh()->year)->toBe(2026)
        ->and($fee->fresh()->due_date?->format('Y-m-d'))->toBe('2026-09-30'));
});

it('bulk edits fee structure type and amount', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $structures = FeeStructure::factory()->count(2)->create();

    Livewire::test(ListFeeStructures::class)
        ->callTableBulkAction('bulkEdit', $structures, [
            'type' => FeeStructureType::OneTime->value,
            'amount' => '12000',
        ]);

    $structures->each(fn (FeeStructure $structure) => expect($structure->fresh()->type)->toBe(FeeStructureType::OneTime)
        ->and((float) $structure->fresh()->amount)->toBe(12000.0));
});

it('bulk edits expense recurrence and amount', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $expenses = Expense::factory()->count(2)->create();

    Livewire::test(ListExpenses::class)
        ->callTableBulkAction('bulkEdit', $expenses, [
            'recurrence' => ExpenseRecurrence::Monthly->value,
            'amount' => '3500',
        ]);

    $expenses->each(fn (Expense $expense) => expect($expense->fresh()->recurrence)->toBe(ExpenseRecurrence::Monthly)
        ->and((float) $expense->fresh()->amount)->toBe(3500.0));
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

    expect((float) $fee->fresh()->amount_paid)->toBe(2000.0);

    $payments->each(fn (Payment $payment) => expect($payment->fresh()->status)->toBe(PaymentStatus::Completed));
});

it('bulk marks pending payrolls as paid', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $payrolls = Payroll::factory()->count(2)->create();

    Livewire::test(ListPayrolls::class)
        ->callTableBulkAction('markPaid', $payrolls);

    $payrolls->each(fn (Payroll $payroll) => expect($payroll->fresh()->status->value)->toBe('paid')
        ->and($payroll->fresh()->paid_at)->not->toBeNull());
});

it('shows and searches the student SR # on fees, attendance and results tables', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $student = Student::factory()->create(['sr_no' => 4242]);
    $fee = Fee::factory()->create(['student_id' => $student->getKey()]);
    Attendance::factory()->create(['student_id' => $student->getKey()]);
    ExamResult::factory()->create(['student_id' => $student->getKey()]);

    Livewire::test(ListFees::class)
        ->assertSee('4242')
        ->searchTable('4242')
        ->assertSee($student->name);

    Livewire::test(ListAttendances::class)
        ->assertSee('4242');

    Livewire::test(ListExamResults::class)
        ->assertSee('4242')
        ->assertSee(ExamResult::query()->where('student_id', $student->getKey())->first()->subject->name);
});

it('bulk edits the class of many staff assignments at once', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $assignments = EloquentCollection::make([
        StaffAssignment::factory()->create(),
        StaffAssignment::factory()->create(),
    ]);
    $newClass = StudentClass::factory()->create();

    Livewire::test(ListStaffAssignments::class)
        ->callTableBulkAction('bulkEdit', $assignments, ['student_class_id' => $newClass->getKey()]);

    $assignments->each(fn (StaffAssignment $assignment) => expect($assignment->fresh()->student_class_id)->toBe($newClass->getKey()));
});

it('bulk approves and rejects assignment requests with an admin note', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $requests = StaffAssignmentRequest::factory()->count(2)->create([
        'action' => AssignmentAction::Add->value,
        'status' => AssignmentRequestStatus::Pending,
    ]);

    Livewire::test(ListStaffAssignmentRequests::class)
        ->callTableBulkAction('approve', $requests);

    $requests->each(fn (StaffAssignmentRequest $request) => expect($request->fresh()->status)->toBe(AssignmentRequestStatus::Approved));

    $pending = StaffAssignmentRequest::factory()->create([
        'action' => AssignmentAction::Remove->value,
        'status' => AssignmentRequestStatus::Pending,
    ]);

    Livewire::test(ListStaffAssignmentRequests::class)
        ->callTableBulkAction('reject', [$pending], ['admin_note' => 'Already covered by another teacher.']);

    expect($pending->fresh()->status)->toBe(AssignmentRequestStatus::Rejected)
        ->and($pending->fresh()->admin_note)->toBe('Already covered by another teacher.');
});
