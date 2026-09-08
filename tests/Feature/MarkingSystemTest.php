<?php

declare(strict_types=1);

use App\Models\GradingScale;
use App\Models\MarkingScheme;
use App\Models\StudentClass;
use App\Models\Subject;
use App\Support\AppSettings;
use App\Support\Grades;

it('falls back to the default 0-100 bounds when no scheme is configured', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    expect(MarkingScheme::boundsFor($class->getKey(), $subject->getKey()))->toBe(['min' => 0.0, 'max' => 100.0]);
});

it('uses the admin marking scheme bounds for a class subject pair', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();

    MarkingScheme::factory()->create([
        'student_class_id' => $class->getKey(),
        'subject_id' => $subject->getKey(),
        'min_marks' => 5,
        'max_marks' => 75,
    ]);

    expect(MarkingScheme::boundsFor($class->getKey(), $subject->getKey()))->toBe(['min' => 5.0, 'max' => 75.0])
        // A different subject of the same class keeps the default bounds.
        ->and(MarkingScheme::boundsFor($class->getKey(), Subject::factory()->create()->getKey()))->toBe(['min' => 0.0, 'max' => 100.0]);
});

it('grades fall back to the built-in boundaries while the scale is empty', function (): void {
    expect(Grades::fromMarks(95, 100))->toBe('A+')
        ->and(Grades::fromMarks(85, 100))->toBe('A')
        ->and(Grades::fromMarks(72, 100))->toBe('B')
        ->and(Grades::fromMarks(65, 100))->toBe('C')
        ->and(Grades::fromMarks(55, 100))->toBe('D')
        ->and(Grades::fromMarks(40, 100))->toBe('F');
});

it('grades follow the admin grading scale once configured', function (): void {
    GradingScale::factory()->create(['name' => 'A++', 'min_percentage' => 95]);
    GradingScale::factory()->create(['name' => 'A+', 'min_percentage' => 90]);
    GradingScale::factory()->create(['name' => 'B', 'min_percentage' => 70]);

    Grades::flushCache();

    expect(Grades::fromMarks(96, 100))->toBe('A++')
        ->and(Grades::fromMarks(91, 100))->toBe('A+')
        ->and(Grades::fromMarks(75, 100))->toBe('B')
        // Below the lowest configured threshold there is no grade but F.
        ->and(Grades::fromMarks(69, 100))->toBe('F');
});

it('orders equal thresholds deterministically when reading the scale', function (): void {
    GradingScale::factory()->create(['name' => 'B', 'min_percentage' => 80]);
    GradingScale::factory()->create(['name' => 'A', 'min_percentage' => 80]);

    Grades::flushCache();

    expect(Grades::boundaries())->toBe(['A' => 80.0, 'B' => 80.0]);
});

it('defaults the exam report mode to grades and can switch to positions', function (): void {
    expect(AppSettings::examReportMode())->toBe('grades');

    AppSettings::set(AppSettings::EXAM_REPORT_MODE, 'positions');

    expect(AppSettings::examReportMode())->toBe('positions');

    AppSettings::set(AppSettings::EXAM_REPORT_MODE, 'grades');

    expect(AppSettings::examReportMode())->toBe('grades');
});
