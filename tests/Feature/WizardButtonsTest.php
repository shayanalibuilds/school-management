<?php

declare(strict_types=1);

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Models\Admin;
use App\Models\Student;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('keeps only the cancel action in the create page footer on every wizard step', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    $actions = invade(Livewire::test(CreateStudent::class)->instance())
        ->getFormActions();

    $names = collect($actions)
        ->map(fn ($action): string => $action->getName())
        ->all();

    expect($names)->toBe(['cancel']);
});

it('renders create and create another inside the wizard so they only appear on the final step', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateStudent::class)
        ->assertSeeHtml('wire:click="createAnother"')
        ->assertSeeHtml('type="submit"');
});

it('hides the create buttons on the edit page', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $student = Student::factory()->create();

    Livewire::test(EditStudent::class, ['record' => $student->getKey()])
        ->assertDontSeeHtml('wire:click="createAnother"');
});

it('keeps save changes and cancel visible at every step on the edit page', function (): void {
    actingAs(Admin::factory()->create(), 'admin');
    $student = Student::factory()->create();

    $actions = invade(Livewire::test(EditStudent::class, ['record' => $student->getKey()])->instance())
        ->getFormActions();

    $names = collect($actions)
        ->map(fn ($action): string => $action->getName())
        ->all();

    expect($names)->toBe(['save', 'cancel']);
});
