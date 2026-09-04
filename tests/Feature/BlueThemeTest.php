<?php

declare(strict_types=1);

use App\Filament\Widgets\AttendanceChart;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentClass;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Livewire\Livewire;

it('uses blue as the primary color of the admin panel', function (): void {
    expect(Filament::getPanel('admin')->getColors()['primary'])->toBe(Color::Blue);
});

it('uses blue as the primary color of the staff panel', function (): void {
    expect(Filament::getPanel('staff')->getColors()['primary'])->toBe(Color::Blue);
});

it('renders the attendance chart bars in blue', function (): void {
    $class = StudentClass::factory()->create(['name' => 'Blue Bar Class']);
    $student = Student::factory()->create(['student_class_id' => $class->getKey()]);
    Attendance::factory()->create(['student_id' => $student->getKey()]);

    Livewire::test(AttendanceChart::class)
        ->assertSuccessful()
        ->assertSeeHtml('#2563eb')
        ->assertDontSeeHtml('#10b981');
});
