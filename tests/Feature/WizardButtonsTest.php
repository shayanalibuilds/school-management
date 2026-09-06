<?php

declare(strict_types=1);

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Models\Admin;
use App\Models\Student;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Read the page's protected form action list without leaking mixed types.
 *
 * @return array<int, string>
 */
function formActionNames(object $page): array
{
    $method = new ReflectionMethod($page, 'getFormActions');

    /** @var array<int, Filament\Actions\Action> $actions */
    $actions = $method->invoke($page);

    return collect($actions)
        ->map(fn (Filament\Actions\Action $action): string => (string) $action->getName())
        ->all();
}

it('keeps only the cancel action in the create page footer on every wizard step', function (): void {
    actingAs(Admin::factory()->create(), 'admin');

    $page = Livewire::test(CreateStudent::class)->instance();

    expect(formActionNames($page))->toBe(['cancel']);
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

    $page = Livewire::test(EditStudent::class, ['record' => $student->getKey()])->instance();

    expect(formActionNames($page))->toBe(['save', 'cancel']);
});
