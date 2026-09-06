<?php

declare(strict_types=1);

use App\Filament\Resources\StudentClasses\Pages\CreateStudentClass;
use App\Filament\Resources\StudentClasses\Pages\EditStudentClass;
use App\Filament\Resources\Subjects\Pages\CreateSubject;
use App\Models\StudentClass;
use App\Models\Subject;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('links subjects to a class when a class is created', function (): void {
    actingAs(App\Models\Admin::factory()->create(), 'admin');

    $english = Subject::factory()->create(['name' => 'English '.Str::random(4)]);
    $maths = Subject::factory()->create(['name' => 'Maths '.Str::random(4)]);

    Livewire::test(CreateStudentClass::class)
        ->fillForm([
            'name' => 'Class '.Str::random(6),
            'subjects' => [$english->getKey(), $maths->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $class = StudentClass::query()->orderByDesc('created_at')->first();

    expect($class->subjects->pluck('id'))->toContain($english->getKey())
        ->and($class->subjects->pluck('id'))->toContain($maths->getKey());
});

it('links classes to a subject when a subject is created', function (): void {
    actingAs(App\Models\Admin::factory()->create(), 'admin');

    $nursery = StudentClass::factory()->create(['name' => 'Nursery '.Str::random(4)]);
    $prep = StudentClass::factory()->create(['name' => 'Prep '.Str::random(4)]);

    Livewire::test(CreateSubject::class)
        ->fillForm([
            'name' => 'Science '.Str::random(6),
            'studentClasses' => [$nursery->getKey(), $prep->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $subject = Subject::query()->orderByDesc('created_at')->first();

    expect($subject->studentClasses->pluck('id'))->toContain($nursery->getKey())
        ->and($subject->studentClasses->pluck('id'))->toContain($prep->getKey())
        ->and($nursery->subjects->pluck('id'))->toContain($subject->getKey());
});

it('updates the subject selection when a class is edited', function (): void {
    actingAs(App\Models\Admin::factory()->create(), 'admin');

    $class = StudentClass::factory()->create();
    $oldSubject = Subject::factory()->create();
    $newSubject = Subject::factory()->create();
    $class->subjects()->attach($oldSubject);

    Livewire::test(EditStudentClass::class, ['record' => $class->getKey()])
        ->fillForm(['subjects' => [$newSubject->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($class->refresh()->subjects->pluck('id'))->toContain($newSubject->getKey())
        ->and($class->subjects->pluck('id'))->not->toContain($oldSubject->getKey());
});
