<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StaffStatus;
use App\Enums\StudentStatus;
use App\Models\Guardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent;
use Generator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ImportController extends Controller
{
    /**
     * Students are matched by their one and only identifier: the GR #.
     */
    public function students(Request $request): RedirectResponse
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $classes = StudentClass::query()->pluck('id', 'name');
        $created = 0;

        foreach ($this->rows($request) as $row) {
            $grNo = mb_trim((string) ($row['gr_no'] ?? ''));
            $name = mb_trim((string) ($row['name'] ?? ''));
            $className = mb_trim((string) ($row['class'] ?? ''));
            $joiningDate = mb_trim((string) ($row['joining_date'] ?? ''));

            if ($grNo === '' || $name === '' || ! $classes->has($className) || $joiningDate === '') {
                continue;
            }

            Student::updateOrCreate(
                ['gr_no' => $grNo],
                [
                    'name' => $name,
                    'student_class_id' => $classes->get($className),
                    'joining_date' => $joiningDate,
                    'status' => StudentStatus::tryFrom((string) ($row['status'] ?? '')) ?? StudentStatus::Active,
                ],
            );

            $created++;
        }

        return back()->with('import_status', "Imported {$created} students.");
    }

    public function staff(Request $request): RedirectResponse
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $created = 0;

        foreach ($this->rows($request) as $row) {
            $name = mb_trim((string) ($row['name'] ?? ''));
            $cnic = mb_trim((string) ($row['cnic'] ?? ''));
            $joiningDate = mb_trim((string) ($row['joining_date'] ?? ''));

            if ($name === '' || $cnic === '' || $joiningDate === '') {
                continue;
            }

            $email = mb_trim((string) ($row['email'] ?? ''));

            Staff::updateOrCreate(
                ['cnic' => $cnic],
                [
                    'name' => $name,
                    'email' => $email !== '' ? $email : null,
                    'phone' => isset($row['phone']) && $row['phone'] !== '' ? (string) $row['phone'] : null,
                    'joining_date' => $joiningDate,
                    'status' => StaffStatus::tryFrom((string) ($row['status'] ?? '')) ?? StaffStatus::Active,
                ],
            );

            $created++;
        }

        return back()->with('import_status', "Imported {$created} staff members.");
    }

    /**
     * Imported parents are linked to their children by GR # right away,
     * so the relation is in place from day one.
     */
    public function parents(Request $request): RedirectResponse
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $created = 0;

        foreach ($this->rows($request) as $row) {
            $name = mb_trim((string) ($row['name'] ?? ''));
            $cnic = mb_trim((string) ($row['cnic'] ?? ''));
            $phone = mb_trim((string) ($row['phone'] ?? ''));

            if ($name === '' || $cnic === '' || $phone === '') {
                continue;
            }

            $parent = StudentParent::updateOrCreate(
                ['cnic' => $cnic],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'occupation' => isset($row['occupation']) && $row['occupation'] !== '' ? (string) $row['occupation'] : null,
                ],
            );

            $parent->students()->syncWithoutDetaching(
                $this->studentsByGrNos((string) ($row['children_gr_no'] ?? ''))
            );

            $created++;
        }

        return back()->with('import_status', "Imported {$created} parents.");
    }

    public function guardians(Request $request): RedirectResponse
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $created = 0;

        foreach ($this->rows($request) as $row) {
            $name = mb_trim((string) ($row['name'] ?? ''));
            $cnic = mb_trim((string) ($row['cnic'] ?? ''));
            $phone = mb_trim((string) ($row['phone'] ?? ''));

            if ($name === '' || $cnic === '' || $phone === '') {
                continue;
            }

            $guardian = Guardian::updateOrCreate(
                ['cnic' => $cnic],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'relation' => isset($row['relation']) && $row['relation'] !== '' ? (string) $row['relation'] : null,
                ],
            );

            $guardian->students()->syncWithoutDetaching(
                $this->studentsByGrNos((string) ($row['students_gr_no'] ?? ''))
            );

            $created++;
        }

        return back()->with('import_status', "Imported {$created} guardians.");
    }

    /**
     * Resolve a semicolon-separated list of GR #s into student ids.
     *
     * @return list<string>
     */
    private function studentsByGrNos(string $list): array
    {
        $grNos = collect(explode(';', $list))
            ->map(fn (string $grNo): string => mb_trim($grNo))
            ->filter(fn (string $grNo): bool => $grNo !== '')
            ->all();

        if ($grNos === []) {
            return [];
        }

        return Student::query()
            ->whereIn('gr_no', $grNos)
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();
    }

    /**
     * @return Generator<array<string, mixed>>
     */
    private function rows(Request $request): Generator
    {
        $handle = fopen($request->file('csv')->getRealPath(), 'r');

        if ($handle === false) {
            return;
        }

        $header = null;

        while (($data = fgetcsv($handle, escape: '\\')) !== false) {
            if ($header === null) {
                $header = array_map(fn (string $column): string => mb_strtolower(mb_trim($column)), $data);

                continue;
            }

            if (count($header) !== count($data)) {
                continue;
            }

            yield array_combine($header, $data);
        }

        fclose($handle);
    }
}
