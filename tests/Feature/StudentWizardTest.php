<?php

declare(strict_types=1);

use App\Enums\StudentStatus;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Models\Admin;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('creates a student through the wizard with only the required fields', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $class = StudentClass::factory()->create();

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'gr_no' => 'GR-2026-0001',
            'name' => 'Ayesha Khan',
            'student_class_id' => $class->getKey(),
            'joining_date' => '2026-09-01',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $student = Student::query()->where('gr_no', 'GR-2026-0001')->firstOrFail();

    expect($student->status)->toBe(StudentStatus::Active)
        ->and($student->joining_date->format('Y-m-d'))->toBe('2026-09-01')
        ->and($student->parents)->toBeEmpty()
        ->and($student->guardians)->toBeEmpty();
});

it('links parents and guardians through the wizard steps', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $class = StudentClass::factory()->create();
    $parent = StudentParent::factory()->create(['name' => 'Imran Khan', 'cnic' => '35202-9999999-9', 'phone' => '03009999999']);
    $guardian = Guardian::factory()->create(['name' => 'Uncle Akram']);

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'gr_no' => 'GR-2026-0002',
            'name' => 'Zainab Khan',
            'student_class_id' => $class->getKey(),
            'joining_date' => '2026-09-01',
            'parents' => [$parent->getKey()],
            'guardians' => [$guardian->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $student = Student::query()->where('gr_no', 'GR-2026-0002')->firstOrFail();

    expect($student->parents->pluck('id'))->toContain($parent->getKey())
        ->and($student->guardians->pluck('id'))->toContain($guardian->getKey());
});

it('saves every optional profile field from the wizard', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $class = StudentClass::factory()->create();

    Livewire::test(CreateStudent::class)
        ->fillForm([
            'gr_no' => 'GR-2026-0003',
            'name' => 'Bilal Ahmed',
            'student_class_id' => $class->getKey(),
            'joining_date' => '2026-09-01',
            'date_of_birth' => '2015-04-12',
            'gender' => 'male',
            'b_form_cnic' => '35202-1111111-1',
            'phone' => '03001112223',
            'previous_school' => 'Iqbal Public School',
            'address' => 'House 12, Street 4, Lahore',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $student = Student::query()->where('gr_no', 'GR-2026-0003')->firstOrFail();

    expect($student->date_of_birth->format('Y-m-d'))->toBe('2015-04-12')
        ->and($student->gender)->toBe('male')
        ->and($student->b_form_cnic)->toBe('35202-1111111-1')
        ->and($student->phone)->toBe('03001112223')
        ->and($student->previous_school)->toBe('Iqbal Public School')
        ->and($student->address)->toBe('House 12, Street 4, Lahore');
});

it('requires only name, gr no, joining date and joining class', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateStudent::class)
        ->call('create')
        ->assertHasFormErrors(['name', 'gr_no', 'student_class_id', 'joining_date']);
});
