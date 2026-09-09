<?php

declare(strict_types=1);

use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Models\Admin;
use App\Models\Staff;
use App\Models\Teacher;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('lists the roster with a registered marker', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    $rosterEntry = Teacher::factory()->create(['name' => 'Ayesha Khan']);
    Teacher::factory()->create(['name' => 'Unregistered Waqar']);

    Staff::factory()->create(['teacher_id' => $rosterEntry->id, 'email' => 'ayesha@school.test']);

    Livewire::test(ListTeachers::class)
        ->assertSee('Ayesha Khan')
        ->assertSee('ayesha@school.test')
        ->assertSee('Not registered');
});

it('lets admins add a teacher to the roster', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateTeacher::class)
        ->fillForm([
            'name' => 'Ayesha Khan',
            'cnic' => '35202-1234567-1',
            'phone' => '03001234567',
        ])
        ->call('create');

    expect(Teacher::query()->where('cnic', '35202-1234567-1')->firstOrFail()->name)->toBe('Ayesha Khan');
});

it('rejects a duplicate cnic on the roster', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Teacher::factory()->create(['cnic' => '35202-1234567-1']);

    Livewire::test(CreateTeacher::class)
        ->fillForm([
            'name' => 'Copycat',
            'cnic' => '35202-1234567-1',
        ])
        ->call('create')
        ->assertHasFormErrors(['cnic']);

    expect(Teacher::query()->where('name', 'Copycat')->exists())->toBeFalse();
});

it('lets admins rename a roster entry and pushes it into the staff account', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    $rosterEntry = Teacher::factory()->create(['name' => 'Old Name']);
    $staff = Staff::factory()->create(['teacher_id' => $rosterEntry->id]);

    Livewire::test(EditTeacher::class, ['record' => $rosterEntry->getKey()])
        ->fillForm(['name' => 'New Name'])
        ->call('save');

    expect($rosterEntry->refresh()->name)->toBe('New Name')
        ->and($staff->refresh()->name)->toBe('New Name');
});

it('keeps staff names locked against every path but the roster', function (): void {
    $staff = Staff::factory()->create(['name' => 'Locked Name']);

    $staff->name = 'Direct Assignment';
    $staff->save();

    $staff->update(['name' => 'Mass Assignment']);

    expect($staff->refresh()->name)->toBe('Locked Name');
});

it('denies staff access to the roster', function (): void {
    actingAs(Staff::factory()->create(), 'staff');

    get('/dashboard/teachers')->assertRedirect();
});
