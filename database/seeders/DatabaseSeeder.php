<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\FeeStatus;
use App\Enums\FeeStructureType;
use App\Enums\StudentStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent;
use App\Models\Subject;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a realistic school.
     */
    public function run(): void
    {
        Admin::factory()->create([
            'name' => 'School Admin',
            'email' => 'admin@school.test',
            'password' => 'password',
        ]);

        $teachers = [
            ['name' => 'Demo Teacher', 'email' => 'teacher@school.test'],
            ['name' => 'Fatima Noor', 'email' => 'fatima@school.test'],
            ['name' => 'Bilal Ahmed', 'email' => 'bilal@school.test'],
        ];

        $staff = collect($teachers)->map(fn (array $teacher): Staff => Staff::factory()->create([
            'name' => $teacher['name'],
            'email' => $teacher['email'],
            'password' => 'password',
        ]));

        $classNames = ['Nursery', 'Prep', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6'];
        $classes = collect($classNames)->map(fn (string $name): StudentClass => StudentClass::factory()->create(['name' => $name]));

        $subjectNames = ['English', 'Urdu', 'Mathematics', 'Science', 'Islamiat'];
        $subjects = collect($subjectNames)->map(fn (string $name): Subject => Subject::factory()->create(['name' => $name]));

        foreach ($classes as $index => $class) {
            $class->subjects()->attach($subjects->slice(0, min(3 + $index % 3, 5))->pluck('id'));
        }

        $assignments = [
            [$staff[0], 0, 'English'], [$staff[0], 2, 'Mathematics'], [$staff[0], 4, 'English'],
            [$staff[1], 1, 'Urdu'], [$staff[1], 3, 'Science'], [$staff[1], 5, 'Urdu'],
            [$staff[2], 6, 'Mathematics'], [$staff[2], 7, 'Islamiat'], [$staff[2], 7, 'Science'],
        ];

        foreach ($assignments as [$teacher, $classIndex, $subjectName]) {
            StaffAssignment::create([
                'staff_id' => $teacher->getKey(),
                'student_class_id' => $classes[$classIndex]->getKey(),
                'subject_id' => $subjects->firstWhere('name', $subjectName)->getKey(),
            ]);
        }

        $students = collect();
        $srCounter = 1;

        foreach ($classes as $class) {
            foreach (range(1, 6) as $seat) {
                $students->push(Student::factory()->create([
                    'sr_no' => $srCounter++,
                    'student_class_id' => $class->getKey(),
                    'status' => StudentStatus::Active,
                ]));
            }
        }

        $parents = StudentParent::factory()->count(12)->create();

        $students->each(function (Student $student, int $index) use ($parents): void {
            $parents[$index % $parents->count()]->students()->attach($student->getKey());
        });

        foreach ($students as $studentIndex => $student) {
            foreach (range(0, 9) as $back) {
                $date = today()->subDays($back);

                if ($date->isWeekend()) {
                    continue;
                }

                Attendance::create([
                    'student_id' => $student->getKey(),
                    'student_class_id' => $student->student_class_id,
                    'staff_id' => $staff[$studentIndex % $staff->count()]->getKey(),
                    'date' => $date,
                    'status' => fake()->randomElement([AttendanceStatus::Present, AttendanceStatus::Present, AttendanceStatus::Present, AttendanceStatus::Absent, AttendanceStatus::Leave]),
                ]);
            }

            foreach ([today()->year - 2, today()->year - 1, (int) today()->year] as $year) {
                foreach ($student->studentClass->subjects as $subject) {
                    ExamResult::create([
                        'student_id' => $student->getKey(),
                        'student_class_id' => $student->student_class_id,
                        'subject_id' => $subject->getKey(),
                        'year' => $year,
                        'marks' => fake()->numberBetween(45, 98),
                        'total_marks' => 100,
                    ]);
                }
            }
        }

        $tuition = FeeStructure::factory()->create(['name' => 'Tuition Fee', 'type' => FeeStructureType::Monthly, 'amount' => 2500]);
        $admission = FeeStructure::factory()->create(['name' => 'Admission Fee', 'type' => FeeStructureType::OneTime, 'amount' => 5000]);

        $students->each(function (Student $student, int $index) use ($tuition, $admission): void {
            Fee::create([
                'fee_structure_id' => $tuition->getKey(),
                'student_id' => $student->getKey(),
                'year' => (int) today()->year,
                'amount' => 2500,
                'amount_paid' => $index % 3 === 0 ? 2500 : ($index % 3 === 1 ? 1000 : 0),
            ]);

            if ($index % 4 === 0) {
                Fee::create([
                    'fee_structure_id' => $admission->getKey(),
                    'student_id' => $student->getKey(),
                    'year' => (int) today()->year,
                    'amount' => 5000,
                    'amount_paid' => 0,
                ]);
            }
        });

        Fee::query()->where('amount_paid', '>=', 2500)->update(['status' => FeeStatus::Paid->value]);
        Fee::query()->whereBetween('amount_paid', [1, 2499])->update(['status' => FeeStatus::Partial->value]);
    }
}
