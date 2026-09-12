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
use App\Models\Teacher;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DatabaseSeeder extends Seeder
{
    private const CHUNK = 250;

    /**
     * Seed the application's database with a realistic school.
     *
     * Every table in this app uses UUID primary keys (HasUuids). Eloquent
     * only mints those ids on save(), so Model::insert() must be given an
     * id on every row. Accounts still go through factories so passwords
     * are hashed; everything else is assembled in memory and written in
     * chunks.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seed();
        });
    }

    private function seed(): void
    {
        $now = now();

        Admin::factory()->create([
            'name' => 'School Admin',
            'email' => 'admin@school.test',
            'password' => 'password',
        ]);

        // Every demo teacher exists on the roster first; their account is
        // linked to it exactly as a real registration would be.
        $teachers = [
            ['name' => 'Demo Teacher', 'email' => 'teacher@school.test', 'cnic' => '35202-1234567-1'],
            ['name' => 'Fatima Noor', 'email' => 'fatima@school.test', 'cnic' => '35202-2345678-2'],
            ['name' => 'Bilal Ahmed', 'email' => 'bilal@school.test', 'cnic' => '35202-3456789-3'],
        ];

        $staff = collect($teachers)->map(function (array $teacher): Staff {
            $rosterEntry = Teacher::query()->create([
                'name' => $teacher['name'],
                'cnic' => $teacher['cnic'],
            ]);

            return Staff::factory()->create([
                'name' => $teacher['name'],
                'email' => $teacher['email'],
                'password' => 'password',
                'teacher_id' => $rosterEntry->id,
            ]);
        });

        $classNames = ['Nursery', 'Prep', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6'];
        $this->insertChunks(
            StudentClass::class,
            $this->attributesFromFactories(
                collect($classNames)->map(fn(string $name) => StudentClass::factory()->make(['name' => $name])),
                $now
            )
        );
        $classesByName = StudentClass::query()->whereIn('name', $classNames)->get()->keyBy('name');
        $classes = collect($classNames)->map(fn(string $name): StudentClass => $classesByName[$name]);

        $subjectNames = ['English', 'Urdu', 'Mathematics', 'Science', 'Islamiat'];
        $this->insertChunks(
            Subject::class,
            $this->attributesFromFactories(
                collect($subjectNames)->map(fn(string $name) => Subject::factory()->make(['name' => $name])),
                $now
            )
        );
        $subjectsByName = Subject::query()->whereIn('name', $subjectNames)->get()->keyBy('name');
        $subjects = collect($subjectNames)->map(fn(string $name): Subject => $subjectsByName[$name]);

        $classSubjectIds = [];
        foreach ($classes as $index => $class) {
            $ids = $subjects->slice(0, min(3 + $index % 3, 5))->pluck('id');
            $classSubjectIds[$class->getKey()] = $ids;
            $class->subjects()->attach($ids);
        }

        $assignments = [
            [$staff[0], 0, 'English'], [$staff[0], 2, 'Mathematics'], [$staff[0], 4, 'English'],
            [$staff[1], 1, 'Urdu'], [$staff[1], 3, 'Science'], [$staff[1], 5, 'Urdu'],
            [$staff[2], 6, 'Mathematics'], [$staff[2], 7, 'Islamiat'], [$staff[2], 7, 'Science'],
        ];
        StaffAssignment::query()->insert(
            collect($assignments)->map(function (array $assignment) use ($classes, $subjects, $now): array {
                [$teacher, $classIndex, $subjectName] = $assignment;

                return [
                    'id' => $this->uuid(),
                    'staff_id' => $teacher->getKey(),
                    'student_class_id' => $classes[$classIndex]->getKey(),
                    'subject_id' => $subjects->firstWhere('name', $subjectName)->getKey(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all()
        );

        $studentRows = [];
        foreach ($classes as $class) {
            foreach (range(1, 6) as $seat) {
                $studentRows[] = $this->attributesFromModel(
                    Student::factory()->make([
                        'student_class_id' => $class->getKey(),
                        'status' => StudentStatus::Active,
                    ]),
                    $now,
                    [
                        'student_class_id' => $class->getKey(),
                        'status' => StudentStatus::Active->value,
                    ]
                );
            }
        }
        $this->insertChunks(Student::class, $studentRows);
        $students = Student::query()->get();

        $this->insertChunks(
            StudentParent::class,
            $this->attributesFromFactories(StudentParent::factory()->count(12)->make(), $now)
        );
        $parents = StudentParent::query()->get();

        $students->values()
            ->groupBy(fn(Student $student, int $index): int => $index % $parents->count())
            ->each(function ($group, int $parentIndex) use ($parents): void {
                $parents[$parentIndex]->students()->attach($group->pluck('id')->all());
            });

        $attendanceRows = [];
        $examRows = [];
        $attendanceStatuses = [
            AttendanceStatus::Present->value,
            AttendanceStatus::Present->value,
            AttendanceStatus::Present->value,
            AttendanceStatus::Absent->value,
            AttendanceStatus::Leave->value,
        ];

        foreach ($students as $studentIndex => $student) {
            $staffId = $staff[$studentIndex % $staff->count()]->getKey();

            foreach (range(0, 9) as $back) {
                $date = today()->subDays($back);
                if ($date->isWeekend()) {
                    continue;
                }

                $attendanceRows[] = [
                    'id' => $this->uuid(),
                    'student_id' => $student->getKey(),
                    'student_class_id' => $student->student_class_id,
                    'staff_id' => $staffId,
                    'date' => $date->toDateString(),
                    'status' => fake()->randomElement($attendanceStatuses),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach ([today()->year - 2, today()->year - 1, (int) today()->year] as $year) {
                foreach ($classSubjectIds[$student->student_class_id] as $subjectId) {
                    $examRows[] = [
                        'id' => $this->uuid(),
                        'student_id' => $student->getKey(),
                        'student_class_id' => $student->student_class_id,
                        'subject_id' => $subjectId,
                        'year' => $year,
                        'marks' => fake()->numberBetween(45, 98),
                        'total_marks' => 100,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        $this->insertChunks(Attendance::class, $attendanceRows);
        $this->insertChunks(ExamResult::class, $examRows);

        $tuition = FeeStructure::factory()->create([
            'name' => 'Tuition Fee',
            'type' => FeeStructureType::Monthly,
            'amount' => 2500,
        ]);
        $admission = FeeStructure::factory()->create([
            'name' => 'Admission Fee',
            'type' => FeeStructureType::OneTime,
            'amount' => 5000,
        ]);

        $feeRows = [];
        $students->values()->each(function (Student $student, int $index) use ($tuition, $admission, $now, &$feeRows): void {
            $paid = $index % 3 === 0 ? 2500 : ($index % 3 === 1 ? 1000 : 0);
            $feeRows[] = [
                'id' => $this->uuid(),
                'fee_structure_id' => $tuition->getKey(),
                'student_id' => $student->getKey(),
                'year' => (int) today()->year,
                'amount' => 2500,
                'amount_paid' => $paid,
                'status' => $paid >= 2500
                    ? FeeStatus::Paid->value
                    : ($paid > 0 ? FeeStatus::Partial->value : FeeStatus::Unpaid->value),
                'paid_at' => $paid >= 2500 ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($index % 4 === 0) {
                $feeRows[] = [
                    'id' => $this->uuid(),
                    'fee_structure_id' => $admission->getKey(),
                    'student_id' => $student->getKey(),
                    'year' => (int) today()->year,
                    'amount' => 5000,
                    'amount_paid' => 0,
                    'status' => FeeStatus::Unpaid->value,
                    'paid_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        });
        $this->insertChunks(Fee::class, $feeRows);
    }

    /**
     * @param  iterable<object>  $models
     * @return list<array<string, mixed>>
     */
    private function attributesFromFactories(iterable $models, mixed $now): array
    {
        return collect($models)
            ->map(fn(object $model): array => $this->attributesFromModel($model, $now))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function attributesFromModel(object $model, mixed $now, array $overrides = []): array
    {
        $attributes = array_merge($model->getAttributes(), $overrides);
        $attributes['id'] ??= $this->uuid();
        $attributes['created_at'] ??= $now;
        $attributes['updated_at'] ??= $now;

        foreach ($attributes as $key => $value) {
            if ($value instanceof BackedEnum) {
                $attributes[$key] = $value->value;
            } elseif ($value instanceof DateTimeInterface) {
                $attributes[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return $attributes;
    }

    /**
     * @param  class-string  $model
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertChunks(string $model, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $model::query()->insert($chunk);
        }
    }

    private function uuid(): string
    {
        return (string) Str::uuid();
    }
}
