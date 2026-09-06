<?php

declare(strict_types=1);

use App\Filament\Resources\Parents\Pages\CreateParent;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\StudentParent;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('links students to a parent when a parent is created', function (): void {
    actingAs(App\Models\Admin::factory()->create(), 'admin');

    $class = StudentClass::factory()->create(['name' => 'Class '.Str::random(4)]);
    $studentA = Student::factory()->create(['student_class_id' => $class->getKey(), 'gr_no' => 'GR-A'.Str::random(3)]);
    $studentB = Student::factory()->create(['student_class_id' => $class->getKey(), 'gr_no' => 'GR-B'.Str::random(3)]);

    Livewire::test(CreateParent::class)
        ->fillForm([
            'name' => 'Test Parent '.Str::random(4),
            'cnic' => '35211-'.Str::random(7).'-'.'1',
            'phone' => '03001234567',
            'students' => [$studentA->getKey(), $studentB->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $parent = StudentParent::query()->orderByDesc('created_at')->first();

    expect($parent->students->pluck('id'))->toContain($studentA->getKey())
        ->and($parent->students->pluck('id'))->toContain($studentB->getKey());
});

it('labels student selects with name, class and gr number', function (): void {
    $class = StudentClass::factory()->create(['name' => 'Nursery Rose']);
    $student = Student::factory()->create(['name' => 'Hassan Raza', 'student_class_id' => $class->getKey(), 'gr_no' => 'GR-90210']);

    expect($student->selectLabel())->toBe('Hassan Raza — Nursery Rose — GR #GR-90210');
});

it('keeps parent contact data out of the students table', function (): void {
    $columns = collect(DB::select('PRAGMA table_info(students)'))->pluck('name');

    expect($columns)->not->toContain('father_name')
        ->not->toContain('sr_no')
        ->toContain('gr_no');
});
