<?php

declare(strict_types=1);

use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\Admin;
use App\Models\FeeStructure;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent;
use App\Models\Subject;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('archives students instead of deleting them', function (): void {
    $student = Student::factory()->create();

    $student->delete();

    expect(Student::query()->find($student->getKey()))->not->toBeNull()
        ->and($student->fresh()->status->value)->toBe('left');
});

it('archives classes, subjects, parents, guardians and fee structures', function (): void {
    $class = StudentClass::factory()->create();
    $subject = Subject::factory()->create();
    $parent = StudentParent::factory()->create();
    $guardian = Guardian::factory()->create();
    $feeStructure = FeeStructure::factory()->create();

    $class->delete();
    $subject->delete();
    $parent->delete();
    $guardian->delete();
    $feeStructure->delete();

    expect(StudentClass::query()->find($class->getKey())->status)->toBe('inactive')
        ->and(Subject::query()->find($subject->getKey())->status)->toBe('inactive')
        ->and(StudentParent::query()->find($parent->getKey())->status)->toBe('inactive')
        ->and(Guardian::query()->find($guardian->getKey())->status)->toBe('inactive')
        ->and(FeeStructure::query()->find($feeStructure->getKey())->status)->toBe('inactive');
});

it('lets archived records come back', function (): void {
    $class = StudentClass::factory()->create();

    $class->archive();

    expect($class->fresh()->status)->toBe('inactive');

    $class->unarchive();
    expect($class->fresh()->status)->toBe('active');
});

it('shows active and inactive tabs on the students list and hides archived records by default', function (): void {
    $admin = Admin::factory()->create();
    $archived = Student::factory()->create(['name' => 'Old Kid']);
    $archived->archive();

    $active = Student::factory()->create(['name' => 'Current Kid']);

    actingAs($admin, 'admin');
    Filament::setCurrentPanel('admin');

    expect(array_keys(Livewire::test(ListStudents::class)->instance()->getTabs()))->toBe(['active', 'inactive']);

    // The Active tab is the default view: archived records stay out of
    // the table until the admin opens the Inactive tab.
    Livewire::test(ListStudents::class)
        ->assertSee('Current Kid')
        ->assertDontSee('Old Kid');

    Livewire::test(ListStudents::class)
        ->set('activeTab', 'inactive')
        ->assertSee('Old Kid')
        ->assertDontSee('Current Kid');
});

it('hides inactive classes from the fill attendance picker', function (): void {
    $admin = Admin::factory()->create();
    $inactive = StudentClass::factory()->create(['name' => 'Closed Class']);
    $inactive->archive();

    $active = StudentClass::factory()->create(['name' => 'Open Class']);

    actingAs($admin, 'admin');
    Filament::setCurrentPanel('admin');

    $page = Livewire::test(App\Filament\Pages\FillAttendance::class);

    expect($page->instance()->classes->pluck('name'))->toContain('Open Class')
        ->not->toContain('Closed Class');
});
