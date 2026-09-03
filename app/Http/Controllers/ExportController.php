<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Staff;
use App\Models\Student;
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
     * @return array<string, array{0: callable(): \Illuminate\Support\Collection<int, mixed>, 1: list<string>, 2: callable(mixed): list<string>}>
     */
    private function exporters(): array
    {
        return [
            'students' => [
                fn (): \Illuminate\Support\Collection => Student::query()->with('studentClass')->orderBy('sr_no')->get(),
                ['sr_no', 'name', 'class', 'joining_date', 'leaving_date', 'status'],
                fn (Student $student): array => [
                    $student->sr_no,
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
                fn (): \Illuminate\Support\Collection => Attendance::query()->with(['student', 'studentClass', 'markedBy'])->orderByDesc('date')->get(),
                ['student_sr_no', 'student_name', 'class', 'date', 'status', 'marked_by'],
                fn (Attendance $attendance): array => [
                    $attendance->student?->sr_no,
                    $attendance->student?->name,
                    $attendance->studentClass?->name,
                    $attendance->date->toDateString(),
                    $attendance->status->value,
                    $attendance->markedBy?->name,
                ],
            ],
            'exam-results' => [
                fn (): \Illuminate\Support\Collection => ExamResult::query()->with(['student', 'studentClass', 'subject'])->orderByDesc('year')->get(),
                ['student_sr_no', 'student_name', 'class', 'subject', 'year', 'marks', 'total_marks', 'grade'],
                fn (ExamResult $result): array => [
                    $result->student?->sr_no,
                    $result->student?->name,
                    $result->studentClass?->name,
                    $result->subject?->name,
                    $result->year,
                    $result->marks,
                    $result->total_marks,
                    $result->grade(),
                ],
            ],
        ];
    }
}
