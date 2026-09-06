<?php

declare(strict_types=1);

use App\Filament\Resources\Guardians\Pages\CreateGuardian;
use App\Filament\Resources\Parents\Pages\CreateParent;
use App\Filament\Resources\Parents\Pages\ListParents;
use App\Models\Admin;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentParent;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('creates a parent through the wizard form', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateParent::class)
        ->fillForm([
            'name' => 'Tariq Mehmood',
            'cnic' => '35202-7777777-7',
            'phone' => '03007777777',
            'occupation' => 'Engineer',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $parent = StudentParent::query()->where('cnic', '35202-7777777-7')->firstOrFail();

    expect($parent->name)->toBe('Tariq Mehmood');
});

it('requires name, cnic and phone for a parent', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateParent::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors(['name', 'cnic', 'phone']);
});

it('lists parents with their linked children count', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $parent = StudentParent::factory()->create(['name' => 'Ahmed Raza']);
    $parent->students()->attach(Student::factory()->count(2)->create()->pluck('id')->all());

    Livewire::test(ListParents::class)
        ->assertSee('Ahmed Raza')
        ->assertSee('2');
});

it('bulk deletes parents', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $parents = StudentParent::factory()->count(2)->create();

    Livewire::test(ListParents::class)
        ->callTableBulkAction('delete', $parents);

    $parents->each(fn (StudentParent $parent) => expect(StudentParent::query()->find($parent->getKey()))->toBeNull()
        ->and($parent->refresh()->trashed())->toBeTrue());
});

it('creates a guardian through the wizard form', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateGuardian::class)
        ->fillForm([
            'name' => 'Uncle Akram',
            'cnic' => '35202-8888888-8',
            'phone' => '03008888888',
            'relation' => 'Uncle',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $guardian = Guardian::query()->where('cnic', '35202-8888888-8')->firstOrFail();

    expect($guardian->relation)->toBe('Uncle');
});

it('requires name, cnic and phone for a guardian', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateGuardian::class)
        ->fillForm([])
        ->call('create')
        ->assertHasFormErrors(['name', 'cnic', 'phone']);
});
