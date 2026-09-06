<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Expense;
use App\Models\Fee;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentParent;
use Illuminate\Http\Response;

final class ExportController extends Controller
{
    public function __invoke(string $type): Response
    {
        $exporters = $this->exporters();

        if (! isset($exporters[$type])) {
            abort(404);
        }

        [$query, $headers, $row] = $exporters[$type];

        $filename = str_replace('-', '_', $type).'_'.today()->toDateString().'.csv';

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers, escape: '\\');

        $query()->each(function ($record) use ($handle, $row): void {
            fputcsv($handle, $row($record), escape: '\\');
        });

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array<string, array{0: callable(): \Illuminate\Database\Eloquent\Collection<int, mixed>, 1: list<string>, 2: callable(mixed): list<string>}>
     */
    private function exporters(): array
    {
        return [
            'students' => [
                fn (): \Illuminate\Support\Collection => Student::query()->with('studentClass')->orderBy('gr_no')->get(),
                ['gr_no', 'name', 'class', 'joining_date', 'leaving_date', 'status'],
                fn (Student $student): array => [
                    $student->gr_no,
                    $student->name,
                    $student->studentClass?->name,
                    $student->joining_date?->toDateString(),
                    $student->leaving_date?->toDateString(),
                    $student->status->value,
                ],
            ],
            'staff' => [
                fn (): \Illuminate\Support\Collection => Staff::query()->orderBy('name')->get(),
                ['name', 'cnic', 'email', 'phone', 'joining_date', 'leaving_date', 'status'],
                fn (Staff $staff): array => [
                    $staff->name,
                    $staff->cnic,
                    $staff->email,
                    $staff->phone,
                    $staff->joining_date?->toDateString(),
                    $staff->leaving_date?->toDateString(),
                    $staff->status->value,
                ],
            ],
            'attendance' => [
                fn (): \Illuminate\Support\Collection => Attendance::query()->with(['student.studentClass', 'markedBy', 'markedByAdmin'])->orderByDesc('date')->get(),
                ['student_gr_no', 'student_name', 'class', 'date', 'status', 'marked_by'],
                fn (Attendance $attendance): array => [
                    $attendance->student?->gr_no,
                    $attendance->student?->name,
                    $attendance->studentClass?->name,
                    $attendance->date->toDateString(),
                    $attendance->status->value,
                    $attendance->markerName(),
                ],
            ],
            'exam-results' => [
                fn (): \Illuminate\Support\Collection => ExamResult::query()->with(['student.studentClass', 'subject'])->orderByDesc('year')->get(),
                ['student_gr_no', 'student_name', 'class', 'subject', 'year', 'marks', 'total_marks', 'grade'],
                fn (ExamResult $result): array => [
                    $result->student?->gr_no,
                    $result->student?->name,
                    $result->studentClass?->name,
                    $result->subject?->name,
                    $result->year,
                    $result->marks,
                    $result->total_marks,
                    $result->grade(),
                ],
            ],
            'fees' => [
                fn (): \Illuminate\Support\Collection => Fee::query()->with(['student.studentClass', 'feeStructure'])->orderByDesc('year')->get(),
                ['student_gr_no', 'student_name', 'class', 'fee', 'year', 'amount', 'amount_paid', 'status', 'due_date'],
                fn (Fee $fee): array => [
                    $fee->student?->gr_no,
                    $fee->student?->name,
                    $fee->student?->studentClass?->name,
                    $fee->feeStructure?->name,
                    $fee->year,
                    $fee->amount,
                    $fee->amount_paid,
                    $fee->status->value,
                    $fee->due_date?->toDateString(),
                ],
            ],
            'payments' => [
                fn (): \Illuminate\Support\Collection => Payment::query()->with(['fee.student.studentClass', 'fee.feeStructure'])->orderByDesc('created_at')->get(),
                ['reference', 'student_gr_no', 'student_name', 'fee', 'amount', 'provider', 'status', 'payer_name', 'paid_at'],
                fn (Payment $payment): array => [
                    $payment->reference,
                    $payment->fee?->student?->gr_no,
                    $payment->fee?->student?->name,
                    $payment->fee?->feeStructure?->name,
                    $payment->amount,
                    $payment->provider->value,
                    $payment->status->value,
                    $payment->payer_name,
                    $payment->paid_at?->toDateTimeString(),
                ],
            ],
            'payrolls' => [
                fn (): \Illuminate\Support\Collection => Payroll::query()->with('staff')->orderByDesc('month')->get(),
                ['staff_name', 'staff_cnic', 'month', 'amount', 'status', 'paid_at'],
                fn (Payroll $payroll): array => [
                    $payroll->staff?->name,
                    $payroll->staff?->cnic,
                    $payroll->month,
                    $payroll->amount,
                    $payroll->status->value,
                    $payroll->paid_at?->toDateString(),
                ],
            ],
            'expenses' => [
                fn (): \Illuminate\Support\Collection => Expense::query()->orderBy('name')->get(),
                ['name', 'description', 'amount', 'recurrence'],
                fn (Expense $expense): array => [
                    $expense->name,
                    $expense->description,
                    $expense->amount,
                    $expense->recurrence->value,
                ],
            ],
            'parents' => [
                fn (): \Illuminate\Support\Collection => StudentParent::query()->with('students')->orderBy('name')->get(),
                ['name', 'cnic', 'phone', 'occupation', 'children'],
                fn (StudentParent $parent): array => [
                    $parent->name,
                    $parent->cnic,
                    $parent->phone,
                    $parent->occupation,
                    $parent->students->map(fn (Student $student): string => $student->name.' (GR #'.$student->gr_no.')')->implode('; '),
                ],
            ],
            'guardians' => [
                fn (): \Illuminate\Support\Collection => Guardian::query()->with('students')->orderBy('name')->get(),
                ['name', 'cnic', 'phone', 'relation', 'students'],
                fn (Guardian $guardian): array => [
                    $guardian->name,
                    $guardian->cnic,
                    $guardian->phone,
                    $guardian->relation,
                    $guardian->students->map(fn (Student $student): string => $student->name.' (GR #'.$student->gr_no.')')->implode('; '),
                ],
            ],
        ];
    }
}
